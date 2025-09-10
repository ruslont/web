// Asosiy JavaScript fayli
document.addEventListener('DOMContentLoaded', function() {
    // Savat funksiyalari
    initializeCart();
    
    // Mahsulotlarni yuklash
    loadProducts();
    
    // Navbar funksiyalari
    setupNavigation();
    
    // Forma validatsiyasi
    setupFormValidation();
});

// Savatni ishga tushirish
function initializeCart() {
    let cart = getCart();
    updateCartCount(cart);
    
    // Savatga qo'shish tugmalari
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-to-cart')) {
            const productId = e.target.dataset.productId;
            addToCart(productId);
        }
    });
    
    // Miqdor tugmalari
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('quantity-btn')) {
            const input = e.target.parentElement.querySelector('.quantity-input');
            let quantity = parseInt(input.value);
            
            if (e.target.classList.contains('decrease') && quantity > 1) {
                input.value = quantity - 1;
            } else if (e.target.classList.contains('increase')) {
                input.value = quantity + 1;
            }
            
            // Yangilash
            updateCartItem(e.target.closest('.cart-item').dataset.productId, input.value);
        }
    });
}

// Savatdan olish
function getCart() {
    const cartJSON = localStorage.getItem('elita_sham_cart');
    return cartJSON ? JSON.parse(cartJSON) : {};
}

// Savatga saqlash
function saveCart(cart) {
    localStorage.setItem('elita_sham_cart', JSON.stringify(cart));
}

// Savatga qo'shish
function addToCart(productId, quantity = 1) {
    let cart = getCart();
    
    if (cart[productId]) {
        cart[productId].quantity += quantity;
    } else {
        cart[productId] = {
            quantity: quantity,
            added: Date.now()
        };
    }
    
    saveCart(cart);
    updateCartCount(cart);
    showNotification('Товар добавлен в корзину');
    
    // Animatsiya
    const button = document.querySelector(`[data-product-id="${productId}"]`);
    if (button) {
        button.classList.add('adding');
        setTimeout(() => button.classList.remove('adding'), 500);
    }
}

// Savat sonini yangilash
function updateCartCount(cart) {
    let totalCount = 0;
    for (const productId in cart) {
        totalCount += cart[productId].quantity;
    }
    
    const countElement = document.getElementById('cart-count');
    if (countElement) {
        countElement.textContent = totalCount;
    }
}

// Mahsulotlarni yuklash
async function loadProducts() {
    try {
        const response = await fetch('api/products.php');
        const products = await response.json();
        
        if (products.length > 0) {
            renderProducts(products);
        }
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

// Mahsulotlarni chiqarish
function renderProducts(products) {
    const container = document.getElementById('products-container');
    if (!container) return;
    
    container.innerHTML = products.map(product => `
        <div class="product-card fade-in">
            <img src="assets/images/${product.image}" alt="${product.name}">
            <div class="product-info">
                <h3>${product.name}</h3>
                <p class="price">${formatPrice(product.price)} руб.</p>
                <div class="product-actions">
                    <button class="btn add-to-cart" data-product-id="${product.id}">
                        В корзину
                    </button>
                    <a href="product.php?id=${product.id}" class="btn btn-outline">
                        Подробнее
                    </a>
                </div>
            </div>
        </div>
    `).join('');
}

// Narxni formatlash
function formatPrice(price) {
    return new Intl.NumberFormat('ru-RU').format(price);
}

// Bildirishnoma ko'rsatish
function showNotification(message, type = 'success') {
    // Soddalashtirilgan bildirishnoma
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#4caf50' : '#f44336'};
        color: white;
        border-radius: 5px;
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Navigation setup
function setupNavigation() {
    // Mobile menu
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
        });
    }
    
    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Form validation
function setupFormValidation() {
    const forms = document.querySelectorAll('form[needs-validation]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            this.classList.add('was-validated');
        });
    });
}

// API requests
async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('API request failed:', error);
        showNotification('Ошибка соединения', 'error');
        throw error;
    }
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Format phone number
function formatPhoneNumber(phone) {
    return phone.replace(/(\d{1})(\d{3})(\d{3})(\d{2})(\d{2})/, '+$1 ($2) $3-$4-$5');
}

// Keyingi fayllar uchun...
