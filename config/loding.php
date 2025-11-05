<style>
  :root {
    --bg: #ffffff;
    --accent: #6c7bff;
    --accent-2: #9af6e3;
  }

  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0
  }

  html,
  body {
    height: 100%
  }

  body {
    font-family: ui-sans-serif, system-ui;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    color: black;
  }

  .loader {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 6px solid rgba(0, 0, 0, 0.08);
    border-top-color: #4f46e5;
    animation: spin 0.9s linear infinite;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg)
    }
  }

  @keyframes hue {
    0% {
      border-top-color: #4f46e5;
      border-right-color: #7c3aed
    }

    50% {
      border-top-color: #7c3aed;
      border-right-color: #4f46e5
    }

    100% {
      border-top-color: #4f46e5;
      border-right-color: #7c3aed
    }
  }

  .glow-text {
    margin-top: 18px;
    font-size: 18px;
    animation: glow 1.8s infinite ease-in-out;
    letter-spacing: .5px;
  }

  @keyframes glow {

    0%,
    100% {
      color: var(--accent-2)
    }

    50% {
      color: var(--accent)
    }
  }
</style>

<div class="loader"></div>

<script>
  // redirect after loading (optional)
  setTimeout(() => {
    // window.location.href = 'home.html';
  }, 2000);
</script>