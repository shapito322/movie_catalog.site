// Управление вкладками профиля
function openTab(evt, tabName) {
    const tabcontents = document.querySelectorAll('.tab-content');
    const tablinks = document.querySelectorAll('.tab-btn');

    // Скрыть все вкладки
    tabcontents.forEach(tab => {
        tab.classList.remove('active');
    });

    // Убрать активный класс со всех кнопок
    tablinks.forEach(link => {
        link.classList.remove('active');
    });

    // Показать выбранную вкладку
    document.getElementById(tabName).classList.add('active');

    // Добавить активный класс к нажатой кнопке
    evt.currentTarget.classList.add('active');
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    // Активируем первую вкладку по умолчанию
    const firstTab = document.querySelector('.tab-btn.active');
    if(firstTab) {
        const tabName = firstTab.getAttribute('onclick').match(/'([^']+)'/)[1];
        openTab({currentTarget: firstTab}, tabName);
    }

    // Инициализация загрузки аватарок
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
});