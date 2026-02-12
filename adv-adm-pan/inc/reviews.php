<?php
// reviews-manager.php

// Конфигурация API
define('REVIEWS_API_BASE_URL', 'http://host.docker.internal:3001/v1/api/admin/products');
define('REVIEWS_API_TIMEOUT', 30);

// Загрузка скриптов и стилей
add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'reviews-manager') !== false) {
        wp_enqueue_style('reviews-manager-style', false);
        wp_enqueue_script('reviews-manager-script', false, ['jquery'], null, true);
    }
});

// Добавляем страницу управления отзывами
add_action('admin_menu', function() {
    add_menu_page(
        'Управление отзывами',
        'Отзывы',
        'manage_options',
        'reviews-manager',
        'render_reviews_manager_page',
        'dashicons-star-filled',
        27
    );
    
    add_submenu_page(
        'reviews-manager',
        'Просмотр отзывов',
        'Просмотр отзывов',
        'manage_options',
        'reviews-manager-view',
        'render_view_reviews_page'
    );
    
    add_submenu_page(
        'reviews-manager',
        'Удалить отзыв',
        'Удалить отзыв',
        'manage_options',
        'reviews-manager-delete',
        'render_delete_review_page'
    );
});

function render_reviews_manager_page() {
    // Вывод сообщений
    if (isset($_GET['message'])) {
        $messages = [
            'deleted' => ['type' => 'success', 'text' => 'Отзыв успешно удален!'],
            'error' => ['type' => 'error', 'text' => 'Произошла ошибка при удалении отзыва']
        ];
        
        if (isset($messages[$_GET['message']])) {
            $msg = $messages[$_GET['message']];
            echo '<div class="notice notice-' . $msg['type'] . ' is-dismissible"><p>' . $msg['text'] . '</p></div>';
        }
    }
    
    if (isset($_GET['error'])) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(urldecode($_GET['error'])) . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Управление отзывами</h1>
        <div class="notice notice-info">
            <p>Используйте эту панель для просмотра и управления отзывами на товары.</p>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <div class="card" style="padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; text-align: center;">
                <div style="font-size: 48px; color: #2271b1; margin-bottom: 10px;">
                    <span class="dashicons dashicons-visibility"></span>
                </div>
                <h3>Просмотр отзывов</h3>
                <p>Просмотрите все отзывы по конкретному товару</p>
                <a href="<?php echo admin_url('admin.php?page=reviews-manager-view'); ?>" class="button button-primary">Перейти к просмотру</a>
            </div>
            
            <div class="card" style="padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; text-align: center;">
                <div style="font-size: 48px; color: #d63638; margin-bottom: 10px;">
                    <span class="dashicons dashicons-trash"></span>
                </div>
                <h3>Удаление отзыва</h3>
                <p>Удалите конкретный отзыв по ID товара и ID отзыва</p>
                <a href="<?php echo admin_url('admin.php?page=reviews-manager-delete'); ?>" class="button" style="background: #d63638; color: white; border-color: #d63638;">Перейти к удалению</a>
            </div>
        </div>
        
        <div class="card" style="margin-top: 30px; padding: 20px; background: #f6f7f7; border: 1px solid #ccd0d4; border-radius: 8px;">
            <h3>Как это работает?</h3>
            <ul style="list-style-type: disc; padding-left: 20px;">
                <li>Для просмотра отзывов введите ID товара в форме</li>
                <li>Система загрузит все отзывы по этому товару</li>
                <li>Вы можете просмотреть детали каждого отзыва</li>
                <li>Для удаления нужны ID товара и ID отзыва</li>
                <li>Удаление невозможно отменить</li>
            </ul>
        </div>
    </div>
    
    <style>
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    </style>
    <?php
}

function render_view_reviews_page() {
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    $reviews_data = null;
    $product_info = null;
    
    if ($product_id > 0) {
        $result = get_reviews_from_api($product_id);
        if ($result['success']) {
            $reviews_data = $result['data'];
            $product_info = $result['metadata']['product'] ?? null;
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>Просмотр отзывов</h1>
        
        <div class="notice notice-info">
            <p>Введите ID товара для просмотра всех отзывов к нему.</p>
        </div>

        <div class="reviews-form-container">
            <div class="reviews-form">
                <div class="form-section">
                    <h3>Загрузка отзывов</h3>
                    <form method="get" action="<?php echo admin_url('admin.php'); ?>">
                        <input type="hidden" name="page" value="reviews-manager-view">
                        <div style="display: flex; gap: 10px; align-items: flex-end;">
                            <div class="form-group" style="flex: 1;">
                                <label for="product-id-input">ID товара*:</label>
                                <input type="number" id="product-id-input" name="product_id" value="<?php echo esc_attr($product_id); ?>" placeholder="123" required min="1">
                                <p class="description" style="font-size: 12px; color: #666; margin-top: 5px;">
                                    Числовой идентификатор товара
                                </p>
                            </div>
                            <button type="submit" class="button button-primary">Загрузить отзывы</button>
                        </div>
                    </form>
                </div>
                
                <?php if ($product_id > 0 && $reviews_data !== null): ?>
                    <?php if ($product_info): ?>
                    <div class="product-info-card" style="background: linear-gradient(135deg, #f6f7f7 0%, #ffffff 100%); border-left: 4px solid #2271b1; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                        <h3 style="margin-top: 0; color: #2271b1;">Информация о товаре</h3>
                        <div style="display: grid; grid-template-columns: auto 1fr; gap: 10px 20px;">
                            <div style="font-weight: 600;">ID:</div>
                            <div><?php echo esc_html($product_info['id'] ?? 'N/A'); ?></div>
                            
                            <div style="font-weight: 600;">Название:</div>
                            <div><?php echo esc_html($product_info['name'] ?? 'N/A'); ?></div>
                            
                            <div style="font-weight: 600;">Категория:</div>
                            <div><?php echo esc_html($product_info['category'] ?? 'N/A'); ?></div>
                            
                            <?php if (isset($product_info['price'])): ?>
                            <div style="font-weight: 600;">Цена:</div>
                            <div><?php echo number_format($product_info['price'], 0, ',', ' '); ?> ₽</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-section">
                        <h3>Отзывы к товару 
                            <span class="badge" style="background: #2271b1; color: white; padding: 2px 8px; border-radius: 12px; font-size: 14px;">
                                <?php echo intval($reviews_data['total'] ?? 0); ?> отзывов
                            </span>
                        </h3>
                        
                        <?php if (!empty($reviews_data['data'])): ?>
                            <div class="reviews-list">
                                <?php foreach ($reviews_data['data'] as $review): ?>
                                    <div class="review-card">
                                        <div class="review-header">
                                            <div class="review-user-info">
                                                <div class="review-user-avatar">
                                                    <span class="dashicons dashicons-admin-users"></span>
                                                </div>
                                                <div class="review-user-details">
                                                    <div class="review-user-name">
                                                        <?php echo esc_html($review['user']['firstName'] ?? 'Пользователь'); ?>
                                                        <?php if (!empty($review['user']['lastName'])): ?>
                                                            <?php echo ' ' . esc_html($review['user']['lastName']); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="review-date">
                                                        <?php echo date('d.m.Y H:i', strtotime($review['createdAt'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="review-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="star <?php echo $i <= $review['rating'] ? 'filled' : 'empty'; ?>">
                                                        ★
                                                    </span>
                                                <?php endfor; ?>
                                                <span class="rating-value"><?php echo esc_html($review['rating']); ?>/5</span>
                                            </div>
                                        </div>
                                        
                                        <div class="review-content">
                                            <div class="review-section">
                                                <div class="review-section-label">Опыт использования:</div>
                                                <div class="review-section-value">
                                                    <?php echo esc_html($review['usageExperience']); ?>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($review['advantages'])): ?>
                                            <div class="review-section">
                                                <div class="review-section-label">Достоинства:</div>
                                                <div class="review-section-value">
                                                    <?php echo esc_html($review['advantages']); ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($review['disadvantages'])): ?>
                                            <div class="review-section">
                                                <div class="review-section-label">Недостатки:</div>
                                                <div class="review-section-value">
                                                    <?php echo esc_html($review['disadvantages']); ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($review['comment'])): ?>
                                            <div class="review-section">
                                                <div class="review-section-label">Комментарий:</div>
                                                <div class="review-section-value">
                                                    <?php echo esc_html($review['comment']); ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($review['photos'])): ?>
                                            <div class="review-section">
                                                <div class="review-section-label">Фотографии:</div>
                                                <div class="review-photos">
                                                    <?php foreach ($review['photos'] as $photo): ?>
                                                        <div class="review-photo">
                                                            <div class="photo-placeholder">
                                                                <span class="dashicons dashicons-camera"></span>
                                                                <span class="photo-name"><?php echo esc_html($photo); ?></span>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="review-actions">
                                            <div class="review-id">
                                                ID отзыва: <strong><?php echo esc_html($review['id']); ?></strong>
                                            </div>
                                            <a href="<?php echo admin_url('admin.php?page=reviews-manager-delete&product_id=' . $product_id . '&review_id=' . $review['id']); ?>" 
                                               class="button button-small" 
                                               style="background: #d63638; color: white; border-color: #d63638;">
                                                Удалить отзыв
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-reviews-message" style="text-align: center; padding: 40px; background: #f6f7f7; border-radius: 8px; border: 2px dashed #ddd;">
                                <span class="dashicons dashicons-format-chat" style="font-size: 48px; color: #a7aaad; margin-bottom: 20px; display: block;"></span>
                                <h3 style="color: #646970; margin-bottom: 10px;">Отзывов пока нет</h3>
                                <p style="color: #8c8f94;">Для этого товара еще не оставили отзывов.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($product_id > 0): ?>
                    <div class="no-data-message" style="text-align: center; padding: 40px; background: #fff8e5; border-radius: 8px; border: 1px solid #ffb900;">
                        <span class="dashicons dashicons-warning" style="font-size: 48px; color: #dba617; margin-bottom: 20px; display: block;"></span>
                        <h3 style="color: #8a6d3b; margin-bottom: 10px;">Товар не найден</h3>
                        <p style="color: #8a6d3b;">Товар с ID <?php echo esc_html($product_id); ?> не существует или недоступен.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
    .reviews-form-container {
        max-width: 1000px;
        margin-top: 20px;
    }
    
    .reviews-form {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        padding: 30px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .form-section {
        margin-bottom: 30px;
        padding-bottom: 30px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #23282d;
    }
    
    input[type="number"] {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }
    
    /* Стили для карточек отзывов */
    .reviews-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .review-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: box-shadow 0.2s, transform 0.2s;
    }
    
    .review-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-1px);
    }
    
    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .review-user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .review-user-avatar {
        width: 50px;
        height: 50px;
        background: #2271b1;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
    }
    
    .review-user-details {
        display: flex;
        flex-direction: column;
    }
    
    .review-user-name {
        font-weight: 600;
        font-size: 16px;
        color: #23282d;
    }
    
    .review-date {
        font-size: 13px;
        color: #646970;
        margin-top: 2px;
    }
    
    .review-rating {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .star {
        font-size: 18px;
    }
    
    .star.filled {
        color: #ffb900;
    }
    
    .star.empty {
        color: #ddd;
    }
    
    .rating-value {
        font-weight: 600;
        color: #23282d;
        margin-left: 8px;
        font-size: 14px;
    }
    
    .review-content {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .review-section {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 15px;
        align-items: start;
    }
    
    .review-section-label {
        font-weight: 600;
        color: #646970;
        font-size: 14px;
    }
    
    .review-section-value {
        color: #23282d;
        line-height: 1.5;
    }
    
    .review-photos {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .review-photo {
        width: 120px;
    }
    
    .photo-placeholder {
        background: #f6f7f7;
        border: 1px dashed #ddd;
        border-radius: 4px;
        padding: 10px;
        text-align: center;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .photo-placeholder:hover {
        background: #f0f0f0;
    }
    
    .photo-placeholder .dashicons {
        font-size: 24px;
        color: #a7aaad;
        display: block;
        margin-bottom: 5px;
    }
    
    .photo-name {
        font-size: 12px;
        color: #646970;
        word-break: break-all;
    }
    
    .review-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
    }
    
    .review-id {
        font-size: 14px;
        color: #646970;
    }
    
    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    @media (max-width: 768px) {
        .review-header {
            flex-direction: column;
            gap: 15px;
        }
        
        .review-section {
            grid-template-columns: 1fr;
            gap: 5px;
        }
        
        .review-actions {
            flex-direction: column;
            gap: 10px;
            align-items: flex-start;
        }
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Автофокус на поле ID товара
        $('#product-id-input').focus();
        
        // Плавная анимация карточек
        $('.review-card').each(function(index) {
            $(this).css({
                'opacity': '0',
                'transform': 'translateY(20px)'
            });
            
            setTimeout(() => {
                $(this).animate({
                    'opacity': '1',
                    'transform': 'translateY(0)'
                }, 300);
            }, index * 100);
        });
    });
    </script>
    <?php
}

function render_delete_review_page() {
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    $review_id = isset($_GET['review_id']) ? intval($_GET['review_id']) : 0;
    $review_data = null;
    $delete_success = false;
    
    // Если есть параметры, загружаем данные отзыва
    if ($product_id > 0 && $review_id > 0) {
        $result = get_specific_review_from_api($product_id, $review_id);
        if ($result['success']) {
            $review_data = $result['data'];
        }
    }
    
    // Обработка удаления
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
        check_admin_referer('delete_review_action');
        
        $product_id = intval($_POST['product_id']);
        $review_id = intval($_POST['review_id']);
        
        $result = delete_review_from_api($product_id, $review_id);
        
        if ($result['success']) {
            $delete_success = true;
            wp_redirect(admin_url('admin.php?page=reviews-manager&message=deleted'));
            exit;
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>Удаление отзыва</h1>
        
        <div class="notice notice-warning">
            <p><strong>Внимание:</strong> Удаление отзыва невозможно отменить. Убедитесь, что вы удаляете правильный отзыв.</p>
        </div>

        <div class="delete-form-container">
            <div class="delete-form">
                <div class="form-section">
                    <h3>Поиск отзыва</h3>
                    <form method="get" action="<?php echo admin_url('admin.php'); ?>">
                        <input type="hidden" name="page" value="reviews-manager-delete">
                        <div class="search-fields" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group">
                                <label for="delete-product-id">ID товара*:</label>
                                <input type="number" id="delete-product-id" name="product_id" value="<?php echo esc_attr($product_id); ?>" placeholder="123" required min="1">
                            </div>
                            <div class="form-group">
                                <label for="delete-review-id">ID отзыва*:</label>
                                <input type="number" id="delete-review-id" name="review_id" value="<?php echo esc_attr($review_id); ?>" placeholder="456" required min="1">
                            </div>
                        </div>
                        <button type="submit" class="button button-primary">Найти отзыв</button>
                    </form>
                </div>
                
                <?php if ($product_id > 0 && $review_id > 0): ?>
                    <?php if ($review_data): ?>
                        <div class="form-section">
                            <h3>Подтверждение удаления</h3>
                            
                            <div class="review-preview-card" style="background: #fff; border: 2px solid #f6f7f7; border-radius: 8px; padding: 25px; margin-bottom: 20px;">
                                <div class="review-preview-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;">
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <div style="width: 50px; height: 50px; background: #d63638; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                                            <span class="dashicons dashicons-admin-users"></span>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; font-size: 16px; color: #23282d;">
                                                <?php echo esc_html($review_data['user']['firstName'] ?? 'Пользователь'); ?>
                                                <?php if (!empty($review_data['user']['lastName'])): ?>
                                                    <?php echo ' ' . esc_html($review_data['user']['lastName']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 13px; color: #646970; margin-top: 2px;">
                                                <?php echo date('d.m.Y H:i', strtotime($review_data['createdAt'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; align-items: center; gap: 5px;">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span style="color: <?php echo $i <= $review_data['rating'] ? '#ffb900' : '#ddd'; ?>; font-size: 18px;">
                                                ★
                                            </span>
                                        <?php endfor; ?>
                                        <span style="font-weight: 600; color: #23282d; margin-left: 8px; font-size: 14px;">
                                            <?php echo esc_html($review_data['rating']); ?>/5
                                        </span>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 150px 1fr; gap: 15px; margin-bottom: 15px;">
                                    <div style="font-weight: 600; color: #646970; font-size: 14px;">Опыт использования:</div>
                                    <div style="color: #23282d;"><?php echo esc_html($review_data['usageExperience']); ?></div>
                                </div>
                                
                                <?php if (!empty($review_data['comment'])): ?>
                                <div style="display: grid; grid-template-columns: 150px 1fr; gap: 15px; margin-bottom: 15px;">
                                    <div style="font-weight: 600; color: #646970; font-size: 14px;">Комментарий:</div>
                                    <div style="color: #23282d;"><?php echo esc_html($review_data['comment']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #f0f0f0;">
                                    <div style="font-size: 14px; color: #646970;">
                                        ID отзыва: <strong><?php echo esc_html($review_data['id']); ?></strong>
                                    </div>
                                    <div style="font-size: 14px; color: #646970;">
                                        ID товара: <strong><?php echo esc_html($product_id); ?></strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="warning-box" style="background: #fff8e5; border: 1px solid #ffb900; border-radius: 4px; padding: 15px; margin-bottom: 20px;">
                                <div style="display: flex; align-items: flex-start; gap: 10px;">
                                    <span class="dashicons dashicons-warning" style="color: #dba617; font-size: 24px;"></span>
                                    <div>
                                        <h4 style="margin-top: 0; color: #8a6d3b;">Внимание!</h4>
                                        <p style="color: #8a6d3b; margin-bottom: 10px;">
                                            Вы собираетесь удалить отзыв пользователя 
                                            <strong><?php echo esc_html($review_data['user']['firstName'] ?? 'Пользователь'); ?></strong>.
                                        </p>
                                        <p style="color: #8a6d3b; margin-bottom: 0;">
                                            Это действие <strong>нельзя отменить</strong>. Отзыв будет удален безвозвратно.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                                <input type="hidden" name="action" value="delete_review">
                                <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">
                                <input type="hidden" name="review_id" value="<?php echo esc_attr($review_id); ?>">
                                <?php wp_nonce_field('delete_review_action'); ?>
                                
                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label for="confirm-checkbox">
                                        <input type="checkbox" id="confirm-checkbox" name="confirm_delete" value="yes" required>
                                        Я понимаю последствия и подтверждаю удаление отзыва
                                    </label>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="button" style="background: #d63638; color: white; border-color: #d63638;" id="delete-button" disabled>
                                        <span class="dashicons dashicons-trash" style="vertical-align: middle; margin-right: 5px;"></span>
                                        Удалить отзыв
                                    </button>
                                    <a href="<?php echo admin_url('admin.php?page=reviews-manager'); ?>" class="button">Отмена</a>
                                </div>
                            </form>
                        </div>
                        
                        <script>
                        jQuery(document).ready(function($) {
                            $('#confirm-checkbox').on('change', function() {
                                if ($(this).is(':checked')) {
                                    $('#delete-button').prop('disabled', false);
                                } else {
                                    $('#delete-button').prop('disabled', true);
                                }
                            });
                            
                            // Подтверждение перед отправкой
                            $('form').on('submit', function(e) {
                                if (!confirm('Вы уверены, что хотите удалить этот отзыв? Это действие нельзя отменить.')) {
                                    e.preventDefault();
                                }
                            });
                        });
                        </script>
                    <?php else: ?>
                        <div class="error-box" style="text-align: center; padding: 40px; background: #fff; border-radius: 8px; border: 2px dashed #ddd;">
                            <span class="dashicons dashicons-dismiss" style="font-size: 48px; color: #d63638; margin-bottom: 20px; display: block;"></span>
                            <h3 style="color: #8a6d3b; margin-bottom: 10px;">Отзыв не найден</h3>
                            <p style="color: #8c8f94;">
                                Отзыв с ID <?php echo esc_html($review_id); ?> для товара с ID <?php echo esc_html($product_id); ?> не найден.
                            </p>
                            <p style="color: #8c8f94; margin-top: 10px;">
                                Проверьте правильность ID товара и ID отзыва.
                            </p>
                        </div>
                    <?php endif; ?>
                <?php elseif ($product_id > 0 || $review_id > 0): ?>
                    <div class="info-box" style="text-align: center; padding: 40px; background: #f6f7f7; border-radius: 8px; border: 1px solid #ddd;">
                        <span class="dashicons dashicons-info" style="font-size: 48px; color: #2271b1; margin-bottom: 20px; display: block;"></span>
                        <h3 style="color: #23282d; margin-bottom: 10px;">Введите данные для поиска</h3>
                        <p style="color: #646970;">
                            Для загрузки отзыва введите ID товара и ID отзыва в поля выше.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
    .delete-form-container {
        max-width: 800px;
        margin-top: 20px;
    }
    
    .delete-form {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        padding: 30px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #f0f0f0;
    }
    
    #delete-button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    @media (max-width: 768px) {
        .search-fields {
            grid-template-columns: 1fr !important;
            gap: 15px !important;
        }
    }
    </style>
    <?php
}

// ===== API ФУНКЦИИ =====

// Получение всех отзывов по товару
function get_reviews_from_api($product_id) {
    $url = REVIEWS_API_BASE_URL . '/' . urlencode($product_id) . '/reviews';
    
    $response = wp_remote_get($url, [
        'timeout' => REVIEWS_API_TIMEOUT,
        'headers' => [
            'Content-Type' => 'application/json',
        ]
    ]);
    
    if (is_wp_error($response)) {
        return ['success' => false, 'message' => 'Ошибка сети: ' . $response->get_error_message()];
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($status_code === 200 && $data) {
        return ['success' => true, 'data' => $data, 'metadata' => $data['metadata'] ?? []];
    } elseif ($status_code === 404) {
        return ['success' => false, 'message' => 'Товар или отзывы не найдены'];
    } else {
        return ['success' => false, 'message' => "Ошибка API: HTTP {$status_code}"];
    }
}

// Получение конкретного отзыва
function get_specific_review_from_api($product_id, $review_id) {
    // Сначала получаем все отзывы
    $result = get_reviews_from_api($product_id);
    
    if (!$result['success']) {
        return $result;
    }
    
    // Ищем конкретный отзыв
    if (!empty($result['data']['data'])) {
        foreach ($result['data']['data'] as $review) {
            if ($review['id'] == $review_id) {
                return ['success' => true, 'data' => $review];
            }
        }
    }
    
    return ['success' => false, 'message' => 'Отзыв не найден'];
}

// Удаление отзыва
function delete_review_from_api($product_id, $review_id) {
    $url = REVIEWS_API_BASE_URL . '/' . urlencode($product_id) . '/reviews/' . urlencode($review_id);
    
    $response = wp_remote_request($url, [
        'method' => 'DELETE',
        'timeout' => REVIEWS_API_TIMEOUT,
        'headers' => [
            'Content-Type' => 'application/json',
        ]
    ]);
    
    if (is_wp_error($response)) {
        return ['success' => false, 'message' => 'Ошибка сети: ' . $response->get_error_message()];
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    
    if ($status_code >= 200 && $status_code < 300) {
        return ['success' => true];
    } else {
        $body = wp_remote_retrieve_body($response);
        return ['success' => false, 'message' => "HTTP {$status_code}: " . $body];
    }
}

// Обработчик удаления отзыва
add_action('admin_post_delete_review', 'handle_delete_review');
function handle_delete_review() {
    check_admin_referer('delete_review_action');
    
    $product_id = intval($_POST['product_id']);
    $review_id = intval($_POST['review_id']);
    
    if ($product_id <= 0 || $review_id <= 0) {
        wp_redirect(admin_url('admin.php?page=reviews-manager-delete&error=Некорректные ID товара или отзыва'));
        exit;
    }
    
    $result = delete_review_from_api($product_id, $review_id);
    
    if ($result['success']) {
        wp_redirect(admin_url('admin.php?page=reviews-manager&message=deleted'));
    } else {
        wp_redirect(admin_url('admin.php?page=reviews-manager-delete&product_id=' . $product_id . '&review_id=' . $review_id . '&error=' . urlencode($result['message'])));
    }
    exit;
}
?>