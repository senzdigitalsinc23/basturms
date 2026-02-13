<div class="card p-3">
  <h3>Make MoMo Payment</h3>
  <form id="momoForm">
    <div>
      <label>Phone Number</label>
      <input type="text" id="phone" name="phone" class="form-control" required>
    </div>
    <div>
      <label>Amount</label>
      <input type="number" id="amount" name="amount" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary mt-2">Pay Now</button>
  </form>

  <!-- Spinner -->
  <div id="loadingSpinner" class="mt-3" style="display:none;">
    <div class="spinner-border text-primary"></div> Processing payment...
  </div>

  <div id="paymentResult" class="mt-3"></div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const momoForm = document.getElementById("momoForm");
    const resultBox = document.getElementById("paymentResult");
    const spinner = document.getElementById("loadingSpinner");

    momoForm.addEventListener("submit", async function (e) {
        e.preventDefault();

        const phone = document.getElementById("phone").value;
        const amount = document.getElementById("amount").value;

        resultBox.innerHTML = "";
        spinner.style.display = "block";

        try {
            const response = await fetch("/api/momo/pay", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({ phone, amount })
            });

            const data = await response.json();
            spinner.style.display = "none";

            if (data.success) {
                resultBox.innerHTML = `<div class="alert alert-info">Payment initiated. Transaction ID: ${data.data.transactionId}. Waiting for confirmation...</div>`;

                // Start polling payment status
                pollPaymentStatus(data.data.transactionId);
            } else {
                resultBox.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
            }
        } catch (err) {
            spinner.style.display = "none";
            resultBox.innerHTML = `<div class="alert alert-danger">Request failed: ${err.message}</div>`;
        }
    });

    async function pollPaymentStatus(transactionId) {
        let interval = setInterval(async () => {
            try {
                const response = await fetch(`/api/momo/status/${transactionId}`, {
                    method: "GET",
                    headers: { "Accept": "application/json" }
                });

                const data = await response.json();

                if (data.success && data.data.status !== "pending") {
                    clearInterval(interval);

                    if (data.data.status === "success") {
                        resultBox.innerHTML = `<div class="alert alert-success">✅ Payment Successful! Ref: ${data.data.reference}</div>`;
                    } else {
                        resultBox.innerHTML = `<div class="alert alert-danger">❌ Payment Failed. Reason: ${data.data.reason}</div>`;
                    }
                }
            } catch (err) {
                clearInterval(interval);
                resultBox.innerHTML = `<div class="alert alert-danger">Error checking status: ${err.message}</div>`;
            }
        }, 5000); // every 5 seconds
    }
});

</script>