<?php
// Settlement calculation functions - can be included by any file

function calculateSettlement($conn, $group_id, $room_id, $from_date, $to_date, $include_food, $include_special, $members) {
    $settlement = [
        'period' => ['from' => $from_date, 'to' => $to_date],
        'food_expenses' => [],
        'special_expenses' => [],
        'settlements' => [],
        'total_food' => 0,
        'total_special' => 0
    ];
    
    if ($include_food) {
        // Get food expenses (regular expenses)
        $stmt = $conn->prepare("SELECT gm.user_id, gm.amount, u.full_name
                               FROM group_messages gm
                               JOIN users u ON gm.user_id = u.id
                               WHERE gm.group_id = ? 
                               AND gm.amount > 0 
                               AND (gm.is_special_item IS NULL OR gm.is_special_item = 0)
                               AND DATE(gm.created_at) BETWEEN ? AND ?");
        $stmt->bind_param("iss", $group_id, $from_date, $to_date);
        $stmt->execute();
        $food_result = $stmt->get_result();
        
        while ($expense = $food_result->fetch_assoc()) {
            $settlement['food_expenses'][] = $expense;
            $settlement['total_food'] += $expense['amount'];
        }
    }
    
    if ($include_special) {
        // Get special item expenses
        $stmt = $conn->prepare("SELECT gm.user_id, gm.amount, u.full_name
                               FROM group_messages gm
                               JOIN users u ON gm.user_id = u.id
                               WHERE gm.group_id = ? 
                               AND gm.amount > 0 
                               AND gm.is_special_item = 1
                               AND DATE(gm.created_at) BETWEEN ? AND ?");
        $stmt->bind_param("iss", $group_id, $from_date, $to_date);
        $stmt->execute();
        $special_result = $stmt->get_result();
        
        while ($expense = $special_result->fetch_assoc()) {
            $settlement['special_expenses'][] = $expense;
            $settlement['total_special'] += $expense['amount'];
        }
    }
    
    // Calculate food settlements (based on actual days present considering approved absences)
    if ($include_food && $settlement['total_food'] > 0) {
        // Calculate actual days present for each member during the settlement period
        $user_days_present = [];
        $total_days_in_period = (strtotime($to_date) - strtotime($from_date)) / (60 * 60 * 24) + 1;
        
        foreach ($members as $user_id => $name) {
            $days_present = $total_days_in_period;
            
            // Get approved absence days for this user during the settlement period
            $absence_stmt = $conn->prepare("SELECT from_date, to_date 
                                           FROM absence_requests 
                                           WHERE room_id = ? AND user_id = ? AND status = 'approved'
                                           AND (
                                               (from_date <= ? AND to_date >= ?) OR
                                               (from_date <= ? AND to_date >= ?) OR
                                               (from_date >= ? AND to_date <= ?)
                                           )");
            $absence_stmt->bind_param("iissssss", $room_id, $user_id, $to_date, $from_date, $from_date, $to_date, $from_date, $to_date);
            $absence_stmt->execute();
            $absence_result = $absence_stmt->get_result();
            
            while ($absence = $absence_result->fetch_assoc()) {
                // Calculate overlap with settlement period
                $absence_start = max(strtotime($absence['from_date']), strtotime($from_date));
                $absence_end = min(strtotime($absence['to_date']), strtotime($to_date));
                
                if ($absence_start <= $absence_end) {
                    $absence_days = ($absence_end - $absence_start) / (60 * 60 * 24) + 1;
                    $days_present -= $absence_days;
                }
            }
            
            $user_days_present[$user_id] = max(0, $days_present);
        }
        
        // Calculate total "person-days" for food cost distribution
        $total_person_days = array_sum($user_days_present);
        
        if ($total_person_days > 0) {
            $cost_per_person_day = $settlement['total_food'] / $total_person_days;
            
            // Group expenses by user
            $user_food_spent = [];
            foreach ($members as $user_id => $name) {
                $user_food_spent[$user_id] = 0;
            }
            
            foreach ($settlement['food_expenses'] as $expense) {
                $user_food_spent[$expense['user_id']] += $expense['amount'];
            }
            
            // Calculate net settlements
            foreach ($members as $user_id => $name) {
                $spent = $user_food_spent[$user_id];
                $days_present = $user_days_present[$user_id];
                $share = $cost_per_person_day * $days_present;
                $net = $spent - $share;
                
                $settlement['settlements'][] = [
                    'user_id' => $user_id,
                    'name' => $name,
                    'type' => 'food',
                    'spent' => $spent,
                    'share' => $share,
                    'net' => $net,
                    'days_present' => $days_present,
                    'total_days' => $total_days_in_period
                ];
            }
        }
    }
    
    // Calculate special item settlements (equal split)
    if ($include_special && $settlement['total_special'] > 0) {
        $special_per_person = $settlement['total_special'] / count($members);
        
        // Group special expenses by user
        $user_special_spent = [];
        foreach ($members as $user_id => $name) {
            $user_special_spent[$user_id] = 0;
        }
        
        foreach ($settlement['special_expenses'] as $expense) {
            $user_special_spent[$expense['user_id']] += $expense['amount'];
        }
        
        // Calculate net settlements
        foreach ($members as $user_id => $name) {
            $spent = $user_special_spent[$user_id];
            $share = $special_per_person;
            $net = $spent - $share;
            
            $settlement['settlements'][] = [
                'user_id' => $user_id,
                'name' => $name,
                'type' => 'special',
                'spent' => $spent,
                'share' => $share,
                'net' => $net
            ];
        }
    }
    
    return $settlement;
}

function generateSettlementSummary($settlement) {
    $summary = "";
    
    // Summary header
    $summary .= "📊 **SETTLEMENT BREAKDOWN**\n\n";
    
    // Food expenses section
    if ($settlement['total_food'] > 0) {
        $summary .= "🍽️ **FOOD EXPENSES**\n";
        $summary .= "Total: ₹" . number_format($settlement['total_food'], 2) . "\n";
        
        // Group food settlements by user
        $food_settlements = [];
        foreach ($settlement['settlements'] as $s) {
            if ($s['type'] == 'food') {
                $food_settlements[$s['user_id']] = $s;
            }
        }
        
        foreach ($food_settlements as $user_data) {
            $net = $user_data['net'];
            $days_present = isset($user_data['days_present']) ? $user_data['days_present'] : ($user_data['total_days'] ?? 'N/A');
            $total_days = isset($user_data['total_days']) ? $user_data['total_days'] : 'N/A';
            
            if ($net > 0.01) {
                $summary .= "• " . $user_data['name'] . ": +₹" . number_format($net, 2) . " (to receive)";
            } elseif ($net < -0.01) {
                $summary .= "• " . $user_data['name'] . ": -₹" . number_format(abs($net), 2) . " (to pay)";
            } else {
                $summary .= "• " . $user_data['name'] . ": ₹0.00 (settled)";
            }
            
            if ($total_days !== 'N/A') {
                $summary .= " | Days: {$days_present}/{$total_days}";
            }
            $summary .= "\n";
        }
        
        // Food payment instructions
        $food_payments = generatePaymentInstructions($food_settlements, 'food');
        if (!empty($food_payments)) {
            $summary .= "\n💸 **Food Payment Instructions:**\n";
            foreach ($food_payments as $payment) {
                $summary .= "• " . $payment['payer'] . " pays " . $payment['receiver'] . " ₹" . number_format($payment['amount'], 2) . "\n";
            }
        }
        $summary .= "\n";
    }
    
    // Special items section
    if ($settlement['total_special'] > 0) {
        $summary .= "⭐ **SPECIAL ITEMS** (Shared Equally)\n";
        $summary .= "Total: ₹" . number_format($settlement['total_special'], 2) . "\n";
        
        // Show who paid for special items
        if (!empty($settlement['special_expenses'])) {
            $special_payers = [];
            foreach ($settlement['special_expenses'] as $expense) {
                if (!isset($special_payers[$expense['full_name']])) {
                    $special_payers[$expense['full_name']] = 0;
                }
                $special_payers[$expense['full_name']] += $expense['amount'];
            }
            
            $summary .= "💳 **Paid By:**\n";
            foreach ($special_payers as $name => $amount) {
                $summary .= "• " . $name . ": ₹" . number_format($amount, 2) . "\n";
            }
        }
        
        // Special item settlements
        $special_settlements = [];
        foreach ($settlement['settlements'] as $s) {
            if ($s['type'] == 'special') {
                $special_settlements[$s['user_id']] = $s;
            }
        }
        
        $summary .= "💰 **Special Settlement:**\n";
        foreach ($special_settlements as $user_data) {
            $net = $user_data['net'];
            if ($net > 0.01) {
                $summary .= "• " . $user_data['name'] . ": +₹" . number_format($net, 2) . " (to receive)\n";
            } elseif ($net < -0.01) {
                $summary .= "• " . $user_data['name'] . ": -₹" . number_format(abs($net), 2) . " (to pay)\n";
            } else {
                $summary .= "• " . $user_data['name'] . ": ₹0.00 (settled)\n";
            }
        }
        
        // Special item payment instructions
        $special_payments = generatePaymentInstructions($special_settlements, 'special');
        if (!empty($special_payments)) {
            $summary .= "\n💸 **Special Item Payment Instructions:**\n";
            foreach ($special_payments as $payment) {
                $summary .= "• " . $payment['payer'] . " pays " . $payment['receiver'] . " ₹" . number_format($payment['amount'], 2) . "\n";
            }
        }
        $summary .= "\n";
    }
    
    // Final combined settlement
    $summary .= "🎯 **FINAL SETTLEMENT** (Food + Special)\n";
    
    // Group all settlements by user
    $user_nets = [];
    foreach ($settlement['settlements'] as $s) {
        if (!isset($user_nets[$s['user_id']])) {
            $user_nets[$s['user_id']] = ['name' => $s['name'], 'net' => 0];
        }
        $user_nets[$s['user_id']]['net'] += $s['net'];
    }
    
    foreach ($user_nets as $user_data) {
        $net = $user_data['net'];
        if ($net > 0.01) {
            $summary .= "• " . $user_data['name'] . ": +₹" . number_format($net, 2) . " (to receive)\n";
        } elseif ($net < -0.01) {
            $summary .= "• " . $user_data['name'] . ": -₹" . number_format(abs($net), 2) . " (to pay)\n";
        } else {
            $summary .= "• " . $user_data['name'] . ": ₹0.00 (settled)\n";
        }
    }
    
    // Combined payment instructions
    $combined_payments = generatePaymentInstructions($user_nets, 'combined');
    if (!empty($combined_payments)) {
        $summary .= "\n💸 **Final Payment Instructions:**\n";
        foreach ($combined_payments as $payment) {
            $summary .= "• " . $payment['payer'] . " pays " . $payment['receiver'] . " ₹" . number_format($payment['amount'], 2) . "\n";
        }
    }
    
    return $summary;
}

function generatePaymentInstructions($settlements, $type = 'combined') {
    $creditors = [];
    $debtors = [];
    
    // Separate creditors and debtors
    foreach ($settlements as $user_id => $data) {
        if ($type === 'combined') {
            $net = $data['net'];
            $name = $data['name'];
        } else {
            $net = $data['net'];
            $name = $data['name'];
        }
        
        if ($net > 0.01) {
            $creditors[] = ['name' => $name, 'amount' => $net];
        } elseif ($net < -0.01) {
            $debtors[] = ['name' => $name, 'amount' => abs($net)];
        }
    }
    
    // Sort by amount (largest first) for optimal settlement
    usort($creditors, function($a, $b) { return $b['amount'] <=> $a['amount']; });
    usort($debtors, function($a, $b) { return $b['amount'] <=> $a['amount']; });
    
    $payments = [];
    $i = 0; $j = 0;
    
    // Generate payment instructions
    while ($i < count($creditors) && $j < count($debtors)) {
        $creditor = $creditors[$i];
        $debtor = $debtors[$j];
        
        $payment_amount = min($creditor['amount'], $debtor['amount']);
        
        if ($payment_amount > 0.01) {
            $payments[] = [
                'payer' => $debtor['name'],
                'receiver' => $creditor['name'],
                'amount' => $payment_amount
            ];
        }
        
        // Update amounts
        $creditors[$i]['amount'] -= $payment_amount;
        $debtors[$j]['amount'] -= $payment_amount;
        
        // Move to next if settled
        if ($creditors[$i]['amount'] < 0.01) $i++;
        if ($debtors[$j]['amount'] < 0.01) $j++;
    }
    
    return $payments;
}
?>
