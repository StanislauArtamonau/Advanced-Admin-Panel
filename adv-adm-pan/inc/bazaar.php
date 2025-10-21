<?php
// Добавляем страницу в админке
add_action('admin_menu', function() {
    add_menu_page(
        'Заявки барахолки',
        'Заявки барахолки',
        'manage_options',
        'bazaar-applications',
        'render_bazaar_applications_page',
        'dashicons-cart',
        25
    );
});

function render_bazaar_applications_page() {
    ?>
    <div class="wrap">
        <h1>🛒 Заявки барахолки</h1>
        
        <div class="notice notice-info">
            <p>Заявки загружаются из API. Для обновления списка обновите страницу.</p>
            <p><strong>Endpoint для отправки заявок:</strong> <code>POST <?php echo get_rest_url(); ?>bazaar/v1/application</code></p>
        </div>

        <div id="bazaar-applications-container">
            <?php display_applications_from_api(); ?>
        </div>
    </div>
    
    <style>
    .bazaar-applications {
        display: grid;
        gap: 25px;
        margin-top: 20px;
    }
    
    .application-card {
        border: 1px solid #ccd0d4;
        border-radius: 12px;
        padding: 25px;
        background: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: box-shadow 0.3s ease;
    }
    
    .application-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .application-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e1e1e1;
    }
    
    .application-title {
        font-size: 20px;
        font-weight: 600;
        margin: 0;
        flex: 1;
        color: #23282d;
    }
    
    .application-price {
        font-size: 22px;
        color: #0073aa;
        font-weight: 700;
        background: #f0f6fc;
        padding: 8px 15px;
        border-radius: 6px;
        border: 1px solid #b6d7e8;
    }
    
    .application-meta {
        margin-top: 8px;
        font-size: 12px;
        color: #666;
    }
    
    .application-meta span {
        margin-right: 15px;
    }
    
    .application-source {
        background: #0073aa;
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
    }
    
    .application-id {
        font-family: monospace;
        background: #f1f1f1;
        padding: 2px 6px;
        border-radius: 3px;
    }
    
    .application-date {
        color: #666;
    }
    
    .application-images {
        display: flex;
        gap: 12px;
        margin: 20px 0;
        flex-wrap: wrap;
    }
    
    .image-wrapper {
        position: relative;
    }
    
    .application-image {
        width: 180px;
        height: 180px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #e1e1e1;
        transition: transform 0.3s ease, border-color 0.3s ease;
    }
    
    .application-image:hover {
        transform: scale(1.05);
        border-color: #0073aa;
    }
    
    .no-images {
        text-align: center;
        padding: 30px;
        background: #f9f9f9;
        border: 2px dashed #ddd;
        border-radius: 8px;
        color: #666;
        margin: 20px 0;
    }
    
    .application-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin: 20px 0;
    }
    
    .application-detail {
        padding: 12px 15px;
        background: #f6f7f7;
        border-radius: 6px;
        border-left: 4px solid #0073aa;
    }
    
    .application-detail strong {
        display: block;
        margin-bottom: 5px;
        color: #23282d;
        font-size: 14px;
    }
    
    .status-pending {
        color: #ffb900;
        font-weight: 500;
    }
    
    .application-description {
        margin: 20px 0;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #46b450;
        line-height: 1.6;
    }
    
    .application-actions {
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #e1e1e1;
        display: flex;
        gap: 12px;
        align-items: center;
    }
    
    .approve-btn {
        background: #46b450;
        border-color: #46b450;
        color: white;
    }
    
    .approve-btn:hover:not(:disabled) {
        background: #3a9543;
        border-color: #3a9543;
    }
    
    .reject-btn {
        background: #dc3232;
        border-color: #dc3232;
        color: white;
    }
    
    .reject-btn:hover:not(:disabled) {
        background: #c12a2a;
        border-color: #c12a2a;
    }
    
    .no-applications {
        text-align: center;
        padding: 50px;
        background: #fff;
        border: 2px dashed #ccd0d4;
        border-radius: 12px;
        color: #666;
    }
    
    .no-applications h3 {
        color: #666;
        margin-bottom: 10px;
    }
    </style>
    <?php
}

// Регистрируем REST API endpoint для приема заявок
add_action('rest_api_init', function() {
    register_rest_route('bazaar/v1', '/application', [
        'methods' => 'POST',
        'callback' => 'handle_application_submission',
        'permission_callback' => 'verify_application_origin'
    ]);
});

function verify_application_origin() {
    // Временно разрешаем все запросы для тестирования
    return true;
}

function handle_application_submission($request) {
    // Получаем JSON данные
    $data = $request->get_json_params();
    
    // Логируем полученные данные для отладки
    error_log('Received application: ' . print_r($data, true));
    
    // Валидация обязательных полей
    $required_fields = ['title', 'category', 'price', 'description', 'region', 'city'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            error_log("Missing required field: $field");
            return new WP_Error('missing_field', "Не заполнено поле: {$field}", ['status' => 400]);
        }
    }
    
    // Создаем уникальный ID если не передан
    $application_id = !empty($data['id']) ? sanitize_text_field($data['id']) : 'app_' . uniqid();
    
    // Сохраняем заявку в опции WordPress (временное хранилище)
    $applications = get_option('bazaar_pending_applications', []);
    
    $new_application = [
        'id' => $application_id,
        'title' => sanitize_text_field($data['title']),
        'category' => sanitize_text_field($data['category']),
        'price' => sanitize_text_field($data['price']),
        'description' => sanitize_textarea_field($data['description']),
        'region' => sanitize_text_field($data['region']),
        'city' => sanitize_text_field($data['city']),
        'images' => !empty($data['images']) ? array_map('esc_url_raw', $data['images']) : [],
        'status' => 'pending',
        'created_at' => current_time('mysql'),
        'source' => 'api'
    ];
    
    // Добавляем новую заявку в начало массива
    array_unshift($applications, $new_application);
    
    // Сохраняем (ограничим количество хранимых заявок до 50)
    if (count($applications) > 50) {
        $applications = array_slice($applications, 0, 50);
    }
    
    update_option('bazaar_pending_applications', $applications);
    
    // Возвращаем успешный ответ
    return [
        'success' => true,
        'application_id' => $application_id,
        'message' => 'Заявка успешно принята',
        'stored_count' => count($applications)
    ];
}

function display_applications_from_api() {
    // Получаем заявки из опций WordPress
    $applications = get_option('bazaar_pending_applications', []);
    
    // Если нет заявок
    if (empty($applications)) {
        echo '<div class="no-applications">';
        echo '<h3>📭 Нет заявок</h3>';
        echo '<p>Заявки появятся здесь после отправки через API</p>';
        echo '<p><strong>Endpoint для тестирования:</strong><br>';
        echo '<code>POST ' . get_rest_url() . 'bazaar/v1/application</code></p>';
        echo '</div>';
        return;
    }
    
    // Отображаем заявки
    echo '<div class="bazaar-applications">';
    
    foreach ($applications as $application) {
        // Экранируем все данные
        $title = esc_html($application['title'] ?? '');
        $category = esc_html($application['category'] ?? '');
        $price = esc_html($application['price'] ?? '');
        $description = wp_kses_post($application['description'] ?? '');
        $region = esc_html($application['region'] ?? '');
        $city = esc_html($application['city'] ?? '');
        $images = $application['images'] ?? [];
        $application_id = esc_html($application['id'] ?? '');
        $created_at = esc_html($application['created_at'] ?? '');
        $source = esc_html($application['source'] ?? 'api');
        ?>
        
        <div class="application-card" data-application-id="<?php echo $application_id; ?>">
            <!-- Заголовок и цена -->
            <div class="application-header">
                <div>
                    <h2 class="application-title"><?php echo $title; ?></h2>
                    <div class="application-meta">
                        <span class="application-id">ID: <?php echo $application_id; ?></span>
                        <span class="application-source">Источник: <?php echo $source; ?></span>
                        <?php if ($created_at): ?>
                        <span class="application-date">Получено: <?php echo date('d.m.Y H:i', strtotime($created_at)); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="application-price"><?php echo $price; ?></div>
            </div>
            
            <!-- Галерея изображений -->
            <?php if (!empty($images) && is_array($images)): ?>
            <div class="application-images">
                <?php foreach ($images as $index => $image_url): 
                    $clean_url = esc_url($image_url);
                ?>
                    <div class="image-wrapper">
                        <img src="<?php echo $clean_url; ?>" 
                             alt="<?php echo $title . ' - фото ' . ($index + 1); ?>" 
                             class="application-image"
                             loading="lazy"
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTgwIiBoZWlnaHQ9IjE4MCIgdmlld0JveD0iMCAwIDE4MCAxODAiIHN0eWxlPSJiYWNrZ3JvdW5kOiAjZjBmMGYwOyBib3JkZXItcmFkaXVzOiA4cHg7Ij48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSIjNjY2Ij7Qp9C40YLQsNC90LggPDwvdGV4dD48dGV4dCB4PSI1MCUiIHk9IjYwJSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5Ij7QvdCw0LfQsNC0PC90ZXh0Pjwvc3ZnPg=='">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-images">
                <p>📷 Изображения не прикреплены</p>
            </div>
            <?php endif; ?>
            
            <!-- Детали заявки -->
            <div class="application-details">
                <div class="application-detail">
                    <strong>Категория:</strong>
                    <?php echo $category; ?>
                </div>
                <div class="application-detail">
                    <strong>Местоположение:</strong>
                    <?php echo $region . ', ' . $city; ?>
                </div>
                <div class="application-detail">
                    <strong>Кол-во фото:</strong>
                    <?php echo count($images); ?> шт.
                </div>
                <div class="application-detail">
                    <strong>Статус:</strong>
                    <span class="status-pending">⏳ На модерации</span>
                </div>
            </div>
            
            <!-- Описание -->
            <?php if (!empty($description)): ?>
            <div class="application-description">
                <strong>Описание:</strong><br>
                <?php echo wpautop($description); ?>
            </div>
            <?php endif; ?>
            
            <!-- Кнопки действий -->
            <div class="application-actions">
                <button type="button" class="button button-primary approve-btn" disabled>✅ Одобрить</button>
                <button type="button" class="button button-secondary reject-btn" disabled>❌ Отклонить</button>
                <span style="margin-left: auto; color: #666; font-size: 13px;">
                    ✅ Получено через API
                </span>
            </div>
        </div>
        
        <?php
    }
    
    echo '</div>';
}
?>