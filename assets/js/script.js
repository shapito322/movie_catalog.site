// Основной скрипт сайта
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация звезд рейтинга
    initStarRating();

    // Инициализация избранного
    initFavorites();

    // Инициализация поиска
    initSearch();

    // Инициализация загрузки аватарок
    initAvatarUpload();
});

// Функция для звезд рейтинга
function initStarRating() {
    const starContainers = document.querySelectorAll('.stars-input');

    starContainers.forEach(container => {
        const stars = container.querySelectorAll('input[type="radio"]');
        const labels = container.querySelectorAll('label');

        stars.forEach((star, index) => {
            star.addEventListener('change', () => {
                // Обновляем визуальное отображение звезд
                labels.forEach((label, labelIndex) => {
                    if(labelIndex <= index) {
                        label.style.backgroundColor = '#ffd700';
                    } else {
                        label.style.backgroundColor = '#ddd';
                    }
                });
            });
        });
    });
}

// Функция для избранного
function initFavorites() {
    const favoriteButtons = document.querySelectorAll('.btn-favorite, .btn-favorite-detail');

    favoriteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const movieId = this.getAttribute('data-movie');
            const isFavorite = this.getAttribute('data-favorite') === 'true';
            const icon = this.querySelector('i');
            const buttonText = this.querySelector('span');

            // Показываем анимацию загрузки
            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            if(buttonText) {
                this.querySelector('span').textContent = '...';
            }

            fetch('api/favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'movie_id=' + movieId + '&action=' + (isFavorite ? 'remove' : 'add')
            })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        if(data.action === 'added') {
                            this.setAttribute('data-favorite', 'true');
                            icon.className = 'fas fa-heart';
                            if(buttonText) {
                                buttonText.textContent = 'В избранном';
                            }
                            this.style.color = '#ff6b6b';
                            showNotification('Фильм добавлен в избранное', 'success');
                        } else {
                            this.setAttribute('data-favorite', 'false');
                            icon.className = 'far fa-heart';
                            if(buttonText) {
                                buttonText.textContent = 'В избранное';
                            }
                            this.style.color = '';
                            showNotification('Фильм удален из избранного', 'info');
                        }
                    } else {
                        showNotification(data.message || 'Ошибка', 'error');
                        this.innerHTML = originalHTML;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Ошибка соединения', 'error');
                    this.innerHTML = originalHTML;
                });
        });
    });
}

// Функция для поиска
function initSearch() {
    const searchInput = document.querySelector('.search-form input');

    if(searchInput) {
        let timeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const searchTerm = this.value.trim();
                if(searchTerm.length >= 2) {
                    performSearch(searchTerm);
                }
            }, 500);
        });
    }
}

// Функция для загрузки аватарок
function initAvatarUpload() {
    const avatarInput = document.getElementById('avatar-input');
    const avatarPreview = document.querySelector('.avatar-preview img');

    if(avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function() {
            if(this.files && this.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                }

                reader.readAsDataURL(this.files[0]);
            }
        });
    }
}

// Выполнение поиска
function performSearch(term) {
    // Реализация AJAX поиска может быть добавлена здесь
    console.log('Searching for:', term);
}

// Уведомления
function showNotification(message, type = 'info') {
    // Удаляем старые уведомления
    const oldNotifications = document.querySelectorAll('.notification');
    oldNotifications.forEach(n => n.remove());

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;

    // Добавляем стили
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-width: 300px;
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;

    if(type === 'success') {
        notification.style.backgroundColor = '#4caf50';
    } else if(type === 'error') {
        notification.style.backgroundColor = '#f44336';
    } else if(type === 'info') {
        notification.style.backgroundColor = '#2196f3';
    }

    notification.querySelector('button').style.cssText = `
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        margin-left: 15px;
    `;

    document.body.appendChild(notification);

    // Автоматическое удаление через 5 секунд
    setTimeout(() => {
        if(notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Анимация загрузки
function showLoader() {
    const loader = document.createElement('div');
    loader.className = 'loader';
    loader.id = 'global-loader';
    loader.innerHTML = '<div class="spinner"></div>';
    loader.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10001;
    `;

    const spinner = loader.querySelector('.spinner');
    spinner.style.cssText = `
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #4ecdc4;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    `;

    // Добавляем анимацию
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);

    document.body.appendChild(loader);
}

function hideLoader() {
    const loader = document.getElementById('global-loader');
    if(loader) {
        loader.remove();
    }
}

// Валидация формы
function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;

    inputs.forEach(input => {
        if(!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }

        // Валидация email
        if(input.type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if(!emailRegex.test(input.value)) {
                input.classList.add('error');
                isValid = false;
            }
        }

        // Валидация пароля
        if(input.type === 'password' && input.hasAttribute('minlength')) {
            const minLength = input.getAttribute('minlength');
            if(input.value.length < minLength) {
                input.classList.add('error');
                isValid = false;
            }
        }
    });

    return isValid;
}

// Обработчик для всех форм
document.addEventListener('submit', function(e) {
    const form = e.target;

    if(form.classList.contains('needs-validation')) {
        e.preventDefault();

        if(validateForm(form)) {
            // Показать загрузку
            showLoader();

            // Отправить форму
            const formData = new FormData(form);

            fetch(form.action || window.location.href, {
                method: form.method,
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    hideLoader();

                    if(data.success) {
                        showNotification(data.message || 'Успешно!', 'success');

                        if(data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1500);
                        }
                    } else {
                        showNotification(data.message || 'Ошибка', 'error');
                    }
                })
                .catch(error => {
                    hideLoader();
                    showNotification('Ошибка соединения', 'error');
                    console.error('Error:', error);
                });
        } else {
            showNotification('Пожалуйста, заполните все обязательные поля правильно', 'error');
        }
    }
});

// Добавление обработчиков для кнопок удаления из избранного в профиле
document.addEventListener('DOMContentLoaded', function() {
    const removeButtons = document.querySelectorAll('.btn-remove-favorite');
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const movieId = this.getAttribute('data-movie');
            const movieCard = this.closest('.movie-card');

            fetch('api/favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'movie_id=' + movieId + '&action=remove'
            })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        movieCard.style.opacity = '0.5';
                        setTimeout(() => {
                            movieCard.remove();
                            showNotification('Фильм удален из избранного', 'success');

                            // Обновить счетчик
                            updateFavoriteCount();
                        }, 300);
                    } else {
                        showNotification(data.message || 'Ошибка', 'error');
                    }
                });
        });
    });
});

// Обновление счетчика избранного
function updateFavoriteCount() {
    const favoriteCount = document.querySelector('.stat:nth-child(1) span');
    if(favoriteCount) {
        const currentCount = parseInt(favoriteCount.textContent);
        favoriteCount.textContent = (currentCount - 1) + ' избранных';
    }
}