document.addEventListener('DOMContentLoaded', function() {
    // Добавление в корзину (для страницы товара)
    document.querySelectorAll('.btn-add-to-cart').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = this.dataset.productId;
            const quantityId = this.dataset.quantityId;
            let quantity = 1;
            
            if (quantityId) {
                const quantityInput = document.getElementById(quantityId);
                if (quantityInput) {
                    quantity = parseInt(quantityInput.value) || 1;
                }
            }
            
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            
            fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ Товар добавлен в корзину');
                } else {
                    alert(data.error || 'Ошибка');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
    
    // Добавление в корзину (для каталога)
    document.querySelectorAll('.btn-add-cart').forEach(button => {
        if (button.classList.contains('disabled')) return;
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            const originalText = this.innerHTML;
            
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', 1);
            
            fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.innerHTML = '✓ В корзине';
                    this.style.background = '#38a169';
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.background = '#16213e';
                    }, 1500);
                } else {
                    alert(data.error || 'Ошибка');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
    
    // Обновление количества в корзине
    document.querySelectorAll('.cart-quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const cartItemId = this.dataset.itemId;
            let quantity = parseInt(this.value);
            
            if (isNaN(quantity) || quantity < 1) {
                quantity = 1;
                this.value = 1;
            }
            
            const formData = new FormData();
            formData.append('cart_item_id', cartItemId);
            formData.append('quantity', quantity);
            
            fetch('update_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const row = this.closest('tr');
                    if (row) {
                        const subtotalCell = row.querySelector('.cart-subtotal');
                        if (subtotalCell) {
                            subtotalCell.textContent = data.subtotal_formatted;
                        }
                    }
                    const totalAmount = document.querySelector('.total-amount');
                    if (totalAmount) {
                        totalAmount.textContent = data.total_formatted;
                    }
                } else {
                    alert(data.error);
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
    
    // Удаление товара из корзины
    document.querySelectorAll('.btn-remove').forEach(button => {
        button.addEventListener('click', function() {
            const cartItemId = this.dataset.itemId;
            
            if (!confirm('Удалить товар из корзины?')) return;
            
            const formData = new FormData();
            formData.append('cart_item_id', cartItemId);
            
            fetch('remove_from_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.cart_empty) {
                        location.reload();
                    } else {
                        const row = this.closest('tr');
                        if (row) {
                            row.remove();
                        }
                        const totalAmount = document.querySelector('.total-amount');
                        if (totalAmount) {
                            totalAmount.textContent = data.total_formatted;
                        }
                        const cartCount = document.querySelector('.cart-count');
                        if (cartCount && data.cart_count !== undefined) {
                            cartCount.textContent = data.cart_count;
                        }
                    }
                } else {
                    alert(data.error);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
});