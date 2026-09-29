<footer class="footer">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 2rem;">
            <div>
                <h3 style="color: var(--neon-pink); margin-bottom: 1rem;">
                    <i class="fas fa-film"></i> Neon Cinema
                </h3>
                <p style="color: var(--text-gray); max-width: 300px;">
                    Самый современный кинотеатр с технологией будущего. 
                    Погружение в мир кино на новом уровне.
                </p>
            </div>
            
            <div>
                <h4 style="color: var(--text-light); margin-bottom: 1rem;">Контакты</h4>
                <p style="color: var(--text-gray);">
                    <i class="fas fa-clock"></i> Ежедневно: 08:00 - 23:00<br>
                    <i class="fas fa-phone"></i> +7 (999) 123-45-67<br>
                    <i class="fas fa-envelope"></i> info@neoncinema.ru
                </p>
            </div>
            
            <div>
                <h4 style="color: var(--text-light); margin-bottom: 1rem;">Быстрые ссылки</h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <a href="/cinema/index.php" style="color: var(--text-gray); text-decoration: none;">
                        <i class="fas fa-chevron-right"></i> Расписание сеансов
                    </a>
                    <a href="/cinema/api/check_ticket.php" style="color: var(--text-gray); text-decoration: none;">
                        <i class="fas fa-chevron-right"></i> Проверить билет
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/cinema/user/orders.php" style="color: var(--text-gray); text-decoration: none;">
                            <i class="fas fa-chevron-right"></i> Мои билеты
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255, 0, 255, 0.1); text-align: center; color: var(--text-gray);">
            <p>&copy; <?php echo date('Y'); ?> Neon Cinema. Все права защищены.</p>
            <p style="font-size: 0.9rem; margin-top: 0.5rem;">
                Система управления кинотеатром | Версия 1.0
            </p>
        </div>
    </div>
</footer>