    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize sidenav
            var elems = document.querySelectorAll('.sidenav');
            M.Sidenav.init(elems);

            // Initialize dropdowns
            var dropdowns = document.querySelectorAll('.dropdown-trigger');
            M.Dropdown.init(dropdowns);

            // Initialize modals
            var modals = document.querySelectorAll('.modal');
            M.Modal.init(modals);

            // Initialize select inputs
            var selects = document.querySelectorAll('select');
            M.FormSelect.init(selects);

            // Initialize tooltips
            var tooltips = document.querySelectorAll('.tooltipped');
            M.Tooltip.init(tooltips);

            // Handle payment method selection
            document.querySelectorAll('input[name="payment_method"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    // Hide all instruction sections
                    document.querySelectorAll('.payment-instruction-section').forEach(function(section) {
                        section.style.display = 'none';
                    });
                    // Show selected method's instructions
                    var selectedMethod = this.getAttribute('data-method');
                    document.getElementById(selectedMethod + '-instructions').style.display = 'block';
                });
            });
        });

        // Function to show subscription modal
        function showSubscribeModal(planId, planName, planPrice) {
            document.getElementById('plan-id').value = planId;
            document.getElementById('plan-name').textContent = planName;
            document.getElementById('plan-price').textContent = planPrice;
            
            // Initialize and open modal
            var modal = document.getElementById('subscribe-modal');
            var instance = M.Modal.getInstance(modal);
            instance.open();
        }

        // Handle file input validation
        document.querySelector('input[name="payment_proof"]').addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                // Check file size (5MB limit)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File is too large. Maximum size is 5MB.');
                    this.value = '';
                    return;
                }
                
                // Check file type
                var validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Invalid file type. Only JPG, PNG, and GIF files are allowed.');
                    this.value = '';
                    return;
                }
            }
        });
    </script>
</body>
</html>
