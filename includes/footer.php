        </div><!-- End of .container -->
        
        <footer>
            <div class="footer-content">
                <div class="footer-section about">
                    <h3><?php echo SITE_NAME; ?></h3>
                    <p>Find your dream home with our comprehensive real estate listing platform. Browse properties, connect with sellers, and make informed decisions.</p>
                    <div class="contact">
                        <span><i class="fas fa-phone"></i> +64 976 212 9904</span>
                        <span><i class="fas fa-envelope"></i> ouenvillarandaa@gmail.com</span>
                    </div>
                    <div class="socials">
                        <a href="https://www.facebook.com/Ashton.Capudoy/"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                
                <div class="footer-section links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php?property_type=house">Houses</a></li>
                        <li><a href="index.php?property_type=apartment">Apartments</a></li>
                        <li><a href="index.php?property_type=condo">Condos</a></li>
                        <li><a href="index.php?property_type=land">Land</a></li>
                        <li><a href="index.php?property_type=commercial">Commercial</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <?php if (isLoggedIn() && hasRole('buyer')): ?>
                        <li><a href="seller_application.php">Become a Seller</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-section contact-form">
                    <h3>Subscribe to Newsletter</h3>
                    <form id="newsletter-form" action="api/newsletter.php" method="post">
                        <input type="email" name="email" class="text-input contact-input" placeholder="Your email address..." required>
                        <button type="submit" class="btn btn-primary">Subscribe</button>
                    </form>
                    <div id="newsletter-message"></div>
                    <script>
                    document.getElementById('newsletter-form').addEventListener('submit', function(e) {
                        e.preventDefault();
                        fetch(this.action, {
                            method: 'POST',
                            body: new FormData(this)
                        })
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById('newsletter-message').innerHTML = 
                                `<div class="alert alert-${data.status === 'success' ? 'success' : 'danger'}">${data.message}</div>`;
                            if(data.status === 'success') this.reset();
                        })
                        .catch(error => {
                            document.getElementById('newsletter-message').innerHTML = 
                                '<div class="alert alert-danger">An error occurred. Please try again.</div>';
                        });
                    });
                    </script>
                </div>
            </div>
            
            <div class="footer-bottom">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> | Created as a Midterm Project
            </div>
        </footer>
        
        <!-- Leaflet JS for maps -->
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <!-- Main JavaScript -->
        <script src="assets/js/main.js"></script>
        
        <!-- Additional page-specific scripts -->
        <?php if (isset($additionalScripts)) echo $additionalScripts; ?>
    </body>
</html>
