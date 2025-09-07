// Savat funksiyalari
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    
    // Savatga qo'shish tugmalari
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            addToCart(productId);
        });
    });
    
    // Miqdorni o'zgartirish
    const quantityButtons = document.querySelectorAll('.quantity-btn');
    quantityButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.quantity-input');
            let quantity = parseInt(input.value);
            
            if (this.classList.contains('decrease')) {
                if (quantity > 1) {
                    input.value = quantity - 1;
                    updateCartItem(this.closest('.cart-item').getAttribute('data-product-id'), input.value);
                }
            } else {
                input.value = quantity + 1;
                updateCartItem(this.closest('.cart-item').getAttribute('data-product-id'), input.value);
            }
        });
    });
    
    // Oʻchirish tugmalari
    const removeButtons = document.querySelectorAll('.remove-from-cart');
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.closest('.cart-item').getAttribute('data-product-id');
            removeFromCart(productId);
        });
    });
    
    // OTP usulini tanlash
    const otpMethods = document.querySelectorAll('.otp-method');
    otpMethods.forEach(method => {
        method.addEventListener('click', function() {
            otpMethods.forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('otp-method').value = this.getAttribute('data-method');
        });
    });
});

// Savatga mahsulot qo'shish
function addToCart(productId) {
    let cart = getCart();
    
    if (cart[productId]) {
        cart[productId].quantity += 1;
    } else {
        cart[productId] = {
            quantity: 1,
            added: new Date().getTime()
        };
    }
    
    saveCart(cart);
    updateCartCount();
    showNotification('Товар добавлен в корзину');
}

// Savatdan mahsulot olib tashlash
function removeFromCart(productId) {
    let cart = getCart();
    delete cart[productId];
    saveCart(cart);
    updateCartCount();
    
    // UI yangilash
    const cartItem = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
    if (cartItem) {
        cartItem.remove();
        updateCartTotal();
    }
}

// Savatdagi mahsulot miqdorini yangilash
function updateCartItem(productId, quantity) {
    let cart = getCart();
    
    if (cart[productId]) {
        cart[productId].quantity = parseInt(quantity);
        saveCart(cart);
        updateCartTotal();
    }
}

// Savatdagi jami summani hisoblash
function updateCartTotal() {
    // Bu funksiya server tomonidan yuklangan narxlardan foydalanishi kerak
    // Hozircha demo versiya
    let total = 0;
    document.querySelectorAll('.cart-item').forEach(item => {
        const price = parseFloat(item.getAttribute('data-price'));
        const quantity = parseInt(item.querySelector('.quantity-input').value);
        total += price * quantity;
        
        // Elementning jami narxini yangilash
        const itemTotal = item.querySelector('.cart-item-total');
        if (itemTotal) {
            itemTotal.textContent = (price * quantity).toLocaleString('ru-RU') + ' руб.';
        }
    });
    
    document.querySelector('.cart-total').textContent = total.toLocaleString('ru-RU') + ' руб.';
}

// Savatdagi mahsulotlar sonini yangilash
function updateCartCount() {
    const cart = getCart();
    let totalCount = 0;
    
    for (const productId in cart) {
        totalCount += cart[productId].quantity;
    }
    
    document.getElementById('cart-count').textContent = totalCount;
}

// LocalStoragedan savatni olish
function getCart() {
    const cartJSON = localStorage.getItem('elita_sham_cart');
    return cartJSON ? JSON.parse(cartJSON) : {};
}

// LocalStoragega savatni saqlash
function saveCart(cart) {
    localStorage.setItem('elita_sham_cart', JSON.stringify(cart));
}

// Bildirishnoma ko'rsatish
function showNotification(message) {
    // Soddalashtirilgan bildirishnoma
    alert(message);
}

// OTP yuborish
function sendOTP() {
    const phone = document.getElementById('phone').value;
    const method = document.getElementById('otp-method').value;
    
    if (!phone) {
        alert('Пожалуйста, введите номер телефона');
        return;
    }
    
    if (!method) {
        alert('Пожалуйста, выберите способ получения кода');
        return;
    }
    
    // AJAX so'rov yuborish
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'send_otp.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            if (response.success) {
                document.getElementById('otp-section').style.display = 'block';
                document.getElementById('otp-timer').textContent = '5:00';
                startOTPTimer();
            } else {
                alert('Ошибка: ' + response.message);
            }
        }
    };
    xhr.send('phone=' + encodeURIComponent(phone) + '&method=' + encodeURIComponent(method));
}

// OTP tekshirish
function verifyOTP() {
    const otp = document.getElementById('otp').value;
    
    if (!otp) {
        alert('Пожалуйста, введите код подтверждения');
        return;
    }
    
    // AJAX so'rov yuborish
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'verify_otp.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            if (response.success) {
                window.location.href = 'checkout.php';
            } else {
                alert('Ошибка: ' + response.message);
            }
        }
    };
    xhr.send('otp=' + encodeURIComponent(otp));
}

// OTP vaqt hisoblagichi
function startOTPTimer() {
    let timeLeft = 300; // 5 daqiqa
    const timerElement = document.getElementById('otp-timer');
    
    const timerInterval = setInterval(function() {
        timeLeft--;
        
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        
        timerElement.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
        
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            alert('Время действия кода истекло. Пожалуйста, запросите новый код.');
        }
    }, 1000);
}
