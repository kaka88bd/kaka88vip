

<html>
 <head> 
  <meta charset="UTF-8"> 
  <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
  <title>Withdraw Funds</title> 
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #2b5876, #4e4376);
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .container {
      background: #fff;
      padding: 30px 40px;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
      width: 100%;
      max-width: 400px;
    }

    .container h2 {
      text-align: center;
      color: #333;
      margin-bottom: 25px;
    }

    label {
      font-weight: bold;
      margin-bottom: 5px;
      display: block;
    }

    input, select, button {
      width: 100%;
      padding: 12px;
      margin-top: 8px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 16px;
    }

    button {
      background: #4e4376;
      color: #fff;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    button:hover {
      background: #2b5876;
    }

    /* Popup */
    .popup {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.6);
      display: flex;
      justify-content: center;
      align-items: center;
      visibility: hidden;
      opacity: 0;
      transition: all 0.3s ease;
      z-index: 999;
    }

    .popup.show {
      visibility: visible;
      opacity: 1;
    }

    .popup-content {
      background: white;
      padding: 25px 30px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      animation: scaleUp 0.4s ease;
    }

    @keyframes scaleUp {
      from { transform: scale(0.7); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }

    .popup-content h3 {
      color: #4e4376;
      margin-bottom: 10px;
    }

    .popup-content button {
      background: #4e4376;
      color: #fff;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      cursor: pointer;
      margin-top: 15px;
    }
  </style> 
 </head> 
 <body> 
  <div class="container"> 
   <h2>Withdraw Funds</h2> 
   <form id="withdrawForm" action="withdraw.php" method="POST" onsubmit="showPopup(event)"> 
    <label for="amount">Amount (৳):</label> 
    <input type="number" id="amount" name="amount" placeholder="Minimum 300৳" min="300" required> 
    <label for="method">Payment Method:</label> 
    <select id="method" name="method" required> <option value="">Select</option> <option value="bkash">bKash</option> <option value="nagad">Nagad</option> <option value="rocket">Rocket</option> </select> 
    <label for="number">Payment Number:</label> 
    <input type="text" id="number" name="number" placeholder="Your Wallet Number" maxlength="11" required> 
    <button type="submit">Request Withdraw</button> 
   </form> 
  </div> 
  <div class="popup" id="popup"> 
   <div class="popup-content"> 
    <h3>Withdrawal Submitted!</h3> 
    <p>Your request has been received.</p> 
    <button onclick="closePopup()">OK</button> 
   </div> 
  </div> 
  <script>
  function showPopup(e) {
    e.preventDefault();
    
    const amount = parseInt(document.getElementById("amount").value);
    if (amount < 300) {
      alert("Minimum withdrawal amount is 300৳.");
      return;
    }

    document.getElementById("popup").classList.add("show");

   
    setTimeout(() => {
      document.getElementById("withdrawForm").submit();
    }, 2000);
  }

  function closePopup() {
    document.getElementById("popup").classList.remove("show");
  }
</script> 
 </body>
</html>