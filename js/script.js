document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const nav = document.querySelector('nav');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            nav.classList.add('active');
            // Create and append close button if it doesn't exist
            if (!document.querySelector('.close-menu')) {
                const closeBtn = document.createElement('div');
                closeBtn.className = 'close-menu';
                closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                nav.appendChild(closeBtn);
                
                closeBtn.addEventListener('click', function() {
                    nav.classList.remove('active');
                });
            }
        });
    }
    
    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (nav.classList.contains('active') && 
            !nav.contains(event.target) && 
            !mobileMenuBtn.contains(event.target)) {
            nav.classList.remove('active');
        }
    });
    
    // Set active class on current page link
    const currentPage = window.location.pathname;
    const navLinks = document.querySelectorAll('nav ul li a');
    
    navLinks.forEach(link => {
        // Get the href attribute
        const linkPath = link.getAttribute('href');
        
        // Check if the current page matches the link
        if (currentPage.endsWith(linkPath) && linkPath !== '#' && linkPath !== 'javascript:void(0)') {
            // Add active class to current link
            link.classList.add('active');
            
            // If this is inside a dropdown, also mark the parent dropdown link as active
            const parentLi = link.closest('li');
            if (parentLi && parentLi.closest('.dropdown-content')) {
                const dropdownLink = parentLi.closest('.dropdown').querySelector('a');
                if (dropdownLink) {
                    dropdownLink.classList.add('active');
                }
            }
        } else if ((currentPage === '/' || currentPage.endsWith('index.php')) && 
                  (linkPath === 'index.php' || linkPath === '/')) {
            // Special case for home page
            link.classList.add('active');
        }
    });
    
    // Mobile dropdown toggle
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const link = dropdown.querySelector('a');
        
        link.addEventListener('click', function(e) {
            // Only for mobile view
            if (window.innerWidth <= 768) {
                e.preventDefault();
                dropdown.classList.toggle('active');
            }
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            const required = form.querySelectorAll('[required]');
            let valid = true;
            
            required.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    // Add error class and message
                    field.classList.add('error');
                    
                    // Create error message if not exists
                    let errorMsg = field.parentElement.querySelector('.error-message');
                    if (!errorMsg) {
                        errorMsg = document.createElement('div');
                        errorMsg.className = 'error-message';
                        field.parentElement.appendChild(errorMsg);
                    }
                    errorMsg.textContent = 'This field is required';
                } else {
                    field.classList.remove('error');
                    const errorMsg = field.parentElement.querySelector('.error-message');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
                
                // Email validation
                if (field.type === 'email' && field.value.trim()) {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(field.value)) {
                        valid = false;
                        field.classList.add('error');
                        
                        let errorMsg = field.parentElement.querySelector('.error-message');
                        if (!errorMsg) {
                            errorMsg = document.createElement('div');
                            errorMsg.className = 'error-message';
                            field.parentElement.appendChild(errorMsg);
                        }
                        errorMsg.textContent = 'Please enter a valid email address';
                    }
                }
                
                // Password validation (if required)
                if (field.type === 'password' && field.value.trim()) {
                    if (field.value.length < 6) {
                        valid = false;
                        field.classList.add('error');
                        
                        let errorMsg = field.parentElement.querySelector('.error-message');
                        if (!errorMsg) {
                            errorMsg = document.createElement('div');
                            errorMsg.className = 'error-message';
                            field.parentElement.appendChild(errorMsg);
                        }
                        errorMsg.textContent = 'Password must be at least 6 characters';
                    }
                }
                
                // CNIC validation (if it's a CNIC field)
                if (field.id === 'cnic' && field.value.trim()) {
                    const cnicPattern = /^\d{5}-\d{7}-\d{1}$/;
                    if (!cnicPattern.test(field.value)) {
                        valid = false;
                        field.classList.add('error');
                        
                        let errorMsg = field.parentElement.querySelector('.error-message');
                        if (!errorMsg) {
                            errorMsg = document.createElement('div');
                            errorMsg.className = 'error-message';
                            field.parentElement.appendChild(errorMsg);
                        }
                        errorMsg.textContent = 'Please enter a valid CNIC (format: 12345-1234567-1)';
                    }
                }
                
                // Mobile validation
                if (field.id === 'mobile' && field.value.trim()) {
                    const mobilePattern = /^\+?[0-9]{10,15}$/;
                    if (!mobilePattern.test(field.value.replace(/\s/g, ''))) {
                        valid = false;
                        field.classList.add('error');
                        
                        let errorMsg = field.parentElement.querySelector('.error-message');
                        if (!errorMsg) {
                            errorMsg = document.createElement('div');
                            errorMsg.className = 'error-message';
                            field.parentElement.appendChild(errorMsg);
                        }
                        errorMsg.textContent = 'Please enter a valid mobile number';
                    }
                }
            });
            
            // Check if password and confirm password match
            const password = form.querySelector('#password');
            const confirmPassword = form.querySelector('#confirm_password');
            
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                valid = false;
                confirmPassword.classList.add('error');
                
                let errorMsg = confirmPassword.parentElement.querySelector('.error-message');
                if (!errorMsg) {
                    errorMsg = document.createElement('div');
                    errorMsg.className = 'error-message';
                    confirmPassword.parentElement.appendChild(errorMsg);
                }
                errorMsg.textContent = 'Passwords do not match';
            }
            
            if (!valid) {
                event.preventDefault();
            }
        });
    });
    
    // Display success or error messages and fade them out after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
    
    // Format CNIC as user types
    setupCNICFormatting();
    
    // Format mobile number
    setupMobileFormatting();
});

// Format CNIC as user types
function setupCNICFormatting() {
    const cnicInput = document.getElementById('cnic');
    if (cnicInput) {
        cnicInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9]/g, ''); // Remove all non-numeric characters
            
            if (value.length > 13) {
                value = value.substr(0, 13);
            }
            
            // Format with hyphens
            if (value.length > 5 && value.length <= 12) {
                value = value.substr(0, 5) + '-' + value.substr(5);
            } else if (value.length > 12) {
                value = value.substr(0, 5) + '-' + value.substr(5, 7) + '-' + value.substr(12);
            }
            
            e.target.value = value;
        });
        
        // Validate on blur
        cnicInput.addEventListener('blur', function(e) {
            const cnicPattern = /^\d{5}-\d{7}-\d{1}$/;
            if (e.target.value && !cnicPattern.test(e.target.value)) {
                e.target.classList.add('error');
                
                let errorMsg = e.target.parentElement.querySelector('.error-message');
                if (!errorMsg) {
                    errorMsg = document.createElement('div');
                    errorMsg.className = 'error-message';
                    e.target.parentElement.appendChild(errorMsg);
                }
                errorMsg.textContent = 'Please enter a valid CNIC (format: 12345-1234567-1)';
            } else {
                e.target.classList.remove('error');
                const errorMsg = e.target.parentElement.querySelector('.error-message');
                if (errorMsg) {
                    errorMsg.remove();
                }
            }
        });
    }
}

// Format mobile number as user types
function setupMobileFormatting() {
    const mobileInput = document.getElementById('mobile');
    if (mobileInput) {
        // Validate on blur
        mobileInput.addEventListener('blur', function(e) {
            const value = e.target.value.replace(/\s/g, '');
            const mobilePattern = /^\+?[0-9]{10,15}$/;
            
            if (value && !mobilePattern.test(value)) {
                e.target.classList.add('error');
                
                let errorMsg = e.target.parentElement.querySelector('.error-message');
                if (!errorMsg) {
                    errorMsg = document.createElement('div');
                    errorMsg.className = 'error-message';
                    e.target.parentElement.appendChild(errorMsg);
                }
                errorMsg.textContent = 'Please enter a valid mobile number';
            } else {
                e.target.classList.remove('error');
                const errorMsg = e.target.parentElement.querySelector('.error-message');
                if (errorMsg) {
                    errorMsg.remove();
                }
            }
        });
    }
}