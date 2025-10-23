<?php
// Конфигурация API
define('BAZAAR_API_BASE_URL', 'http://localhost:3001/v1/api/marketplace/ads/admin/moderation'); // Замени на реальный URL
define('BAZAAR_API_TIMEOUT', 30);

// Добавляем страницу в админке
add_action('admin_menu', function() {
    add_menu_page(
        'Модерация барахолки',
        '🛒 Модерация',
        'manage_options',
        'bazaar-moderation',
        'render_bazaar_moderation_page',
        'dashicons-cart',
        25
    );
});

function render_bazaar_moderation_page() {
    wp_enqueue_script('bazaar-admin', get_template_directory_uri() . '/js/bazaar-admin.js', ['jquery'], '1.0', true);
    wp_localize_script('bazaar-admin', 'bazaar_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bazaar_nonce'),
        'api_base_url' => BAZAAR_API_BASE_URL
    ]);
    ?>
    <div class="wrap">
        <h1>🛒 Модерация объявлений</h1>
        
        <div class="bazaar-header">
            <div class="notice notice-info">
                <p>Заявки загружаются с бэкенда. Для обновления списка обновите страницу.</p>
                <p><strong>API Endpoint:</strong> <code>GET <?php echo BAZAAR_API_BASE_URL; ?>/ads/admin/moderation</code></p>
            </div>
            
            <div class="bazaar-controls">
                <button type="button" class="button button-secondary" id="refresh-applications">
                    🔄 Обновить список
                </button>
                <span class="bazaar-stats" id="applications-stats"></span>
            </div>
        </div>

        <div id="bazaar-applications-container">
            <?php display_applications_from_api(); ?>
        </div>
    </div>
    
    <style>
    .bazaar-header {
        margin-bottom: 20px;
    }
    
    .bazaar-controls {
        display: flex;
        align-items: center;
        gap: 15px;
        margin: 15px 0;
        padding: 15px;
        background: #f6f7f7;
        border-radius: 6px;
    }
    
    .bazaar-stats {
        color: #666;
        font-size: 14px;
    }
    
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
        font-size: 13px;
        color: #666;
    }
    
    .application-meta span {
        margin-right: 15px;
        display: inline-block;
    }
    
    .seller-name {
        background: #e7f3ff;
        padding: 2px 8px;
        border-radius: 4px;
        color: #0073aa;
    }
    
    .application-phone {
        font-family: monospace;
        background: #f1f1f1;
        padding: 2px 6px;
        border-radius: 3px;
    }
    
    .application-condition {
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
    
    .attributes-list {
        margin-top: 10px;
    }
    
    .attribute-item {
        padding: 5px 0;
        border-bottom: 1px solid #e1e1e1;
    }
    
    .attribute-item:last-child {
        border-bottom: none;
    }
    
    .status-pending {
        color: #ffb900;
        font-weight: 500;
    }
    
    .status-approved {
        color: #46b450;
        font-weight: 500;
    }
    
    .status-rejected {
        color: #dc3232;
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
        align-items: flex-start;
    }
    
    .approve-btn, .reject-btn {
        transition: all 0.3s ease;
    }
    
    .approve-btn {
        background: #46b450;
        border-color: #46b450;
        color: white;
    }
    
    .approve-btn:hover:not(:disabled) {
        background: #3a9543;
        border-color: #3a9543;
        transform: translateY(-1px);
    }
    
    .reject-btn {
        background: #dc3232;
        border-color: #dc3232;
        color: white;
    }
    
    .reject-btn:hover:not(:disabled) {
        background: #c12a2a;
        border-color: #c12a2a;
        transform: translateY(-1px);
    }
    
    .rejection-reason {
        margin-top: 15px;
        padding: 15px;
        background: #f8f0f0;
        border: 1px solid #e2b3b3;
        border-radius: 6px;
        display: none;
        flex: 1;
    }
    
    .rejection-reason textarea {
        width: 100%;
        height: 80px;
        margin-top: 10px;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        resize: vertical;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 10px;
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
    
    .application-processed {
        opacity: 0.7;
        background: #f9f9f9;
    }
    
    .loading-spinner {
        text-align: center;
        padding: 40px;
    }
    
    .error-notice {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
        padding: 15px;
        border-radius: 6px;
        margin: 15px 0;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Обновление списка
        $('#refresh-applications').on('click', function() {
            loadApplications();
        });
        
        // Одобрение заявки
        $(document).on('click', '.approve-btn', function() {
            var $card = $(this).closest('.application-card');
            var applicationId = $card.data('application-id');
            
            if (confirm('Вы уверены, что хотите одобрить это объявление?')) {
                processApplication(applicationId, 'approve');
            }
        });
        
        // Отклонение заявки
        $(document).on('click', '.reject-btn', function() {
            var $card = $(this).closest('.application-card');
            var $reasonSection = $card.find('.rejection-reason');
            $reasonSection.show();
        });
        
        // Подтверждение отклонения
        $(document).on('click', '.confirm-reject', function() {
            var $card = $(this).closest('.application-card');
            var applicationId = $card.data('application-id');
            var reason = $card.find('.rejection-reason-text').val();
            
            if (!reason.trim()) {
                alert('Пожалуйста, укажите причину отказа');
                return;
            }
            
            processApplication(applicationId, 'reject', reason);
        });
        
        // Отмена отклонения
        $(document).on('click', '.cancel-reject', function() {
            $(this).closest('.rejection-reason').hide().find('textarea').val('');
        });
        
        function processApplication(applicationId, action, reason = '') {
            var $card = $('.application-card[data-application-id="' + applicationId + '"]');
            var $buttons = $card.find('.approve-btn, .reject-btn');
            
            $buttons.prop('disabled', true).text('Обработка...');
            
            $.ajax({
                url: bazaar_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'process_bazaar_application',
                    application_id: applicationId,
                    action_type: action,
                    reason: reason,
                    nonce: bazaar_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $card.addClass('application-processed');
                        $card.find('.status-pending')
                             .removeClass('status-pending')
                             .addClass('status-' + (action === 'approve' ? 'approved' : 'rejected'))
                             .text(action === 'approve' ? '✅ Одобрено' : '❌ Отклонено');
                        
                        $buttons.remove();
                        $card.find('.rejection-reason').remove();
                        
                        var resultText = action === 'approve' ? 
                            '✅ Объявление одобрено' : 
                            '❌ Объявление отклонено';
                        $card.find('.application-actions').html('<div class="notice notice-' + 
                            (action === 'approve' ? 'success' : 'error') + '"><p>' + resultText + '</p></div>');
                    } else {
                        alert('Ошибка: ' + response.data);
                        $buttons.prop('disabled', false).text(function() {
                            return $(this).hasClass('approve-btn') ? '✅ Одобрить' : '❌ Отклонить';
                        });
                    }
                },
                error: function() {
                    alert('Произошла ошибка при обработке заявки');
                    $buttons.prop('disabled', false).text(function() {
                        return $(this).hasClass('approve-btn') ? '✅ Одобрить' : '❌ Отклонить';
                    });
                }
            });
        }
        
        function loadApplications() {
            $('#bazaar-applications-container').html('<div class="loading-spinner">🔄 Загрузка...</div>');
            
            $.ajax({
                url: bazaar_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_bazaar_applications',
                    nonce: bazaar_ajax.nonce
                },
                success: function(response) {
                    $('#bazaar-applications-container').html(response.data);
                    updateStats();
                },
                error: function() {
                    $('#bazaar-applications-container').html('<div class="error-notice">Ошибка при загрузке заявок</div>');
                }
            });
        }
        
        function updateStats() {
            var count = $('.application-card').length;
            var pending = $('.status-pending').length;
            $('#applications-stats').text('Всего: ' + count + ', На модерации: ' + pending);
        }
        
        // Initial stats
        updateStats();
    });
    </script>
    <?php
}

// AJAX обработчик для загрузки заявок
add_action('wp_ajax_load_bazaar_applications', 'load_bazaar_applications_ajax');

function load_bazaar_applications_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'bazaar_nonce')) {
        wp_die('Security check failed');
    }
    
    ob_start();
    display_applications_from_api();
    $output = ob_get_clean();
    
    wp_send_json_success($output);
}

// AJAX обработчик для модерации заявок
add_action('wp_ajax_process_bazaar_application', 'handle_application_moderation');

function handle_application_moderation() {
    if (!wp_verify_nonce($_POST['nonce'], 'bazaar_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    $application_id = sanitize_text_field($_POST['application_id']);
    $action_type = sanitize_text_field($_POST['action_type']); // 'approve' или 'reject'
    $reason = sanitize_textarea_field($_POST['reason']);
    
    // Получаем заявку из временного хранилища
    $applications = get_option('bazaar_applications_cache', []);
    $application = null;
    
    foreach ($applications as $app) {
        if ($app['id'] == $application_id) {
            $application = $app;
            break;
        }
    }
    
    if (!$application) {
        wp_send_json_error('Заявка не найдена');
    }
    
    // Отправляем запрос на бэкенд
    $result = send_moderation_action($application_id, $action_type, $reason, $application);
    
    if ($result['success']) {
        wp_send_json_success('Заявка обработана');
    } else {
        wp_send_json_error($result['message']);
    }
}

// Функция отправки действия модерации на бэкенд
function send_moderation_action($application_id, $action, $reason = '', $application_data = []) {
    $url = BAZAAR_API_BASE_URL . "/ads/admin/{$application_id}/" . ($action === 'approve' ? 'approve' : 'reject');
    
    $args = [
        'timeout' => BAZAAR_API_TIMEOUT,
        'headers' => [
            'Content-Type' => 'application/json',
        ]
    ];
    
    if ($action === 'reject' && !empty($reason)) {
        $args['body'] = json_encode($reason); // Отправляем строку с комментарием
        $args['headers']['Content-Type'] = 'application/json';
    }
    
    $response = wp_remote_post($url, $args);
    
    if (is_wp_error($response)) {
        return [
            'success' => false,
            'message' => 'Ошибка сети: ' . $response->get_error_message()
        ];
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    
    if ($status_code >= 200 && $status_code < 300) {
        error_log("Successfully {$action}d application {$application_id}");
        return ['success' => true];
    } else {
        $body = wp_remote_retrieve_body($response);
        error_log("Failed to {$action} application {$application_id}. Status: {$status_code}, Response: {$body}");
        return [
            'success' => false,
            'message' => "HTTP {$status_code}: " . $body
        ];
    }
}

// Функция получения заявок с бэкенда
function get_applications_from_backend() {
    $url = BAZAAR_API_BASE_URL . '/ads/admin/moderation';
    
    $response = wp_remote_get($url, [
        'timeout' => BAZAAR_API_TIMEOUT,
        'headers' => [
            'Content-Type' => 'application/json',
        ]
    ]);
    
    if (is_wp_error($response)) {
        error_log('Error fetching applications: ' . $response->get_error_message());
        return [];
    }
    
    $body = wp_remote_retrieve_body($response);
    $applications = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON parse error: ' . json_last_error_msg());
        return [];
    }
    
    // Сохраняем в кэш с ID как ключ
    $applications_with_ids = [];
    foreach ($applications as $index => $app) {
        $app['id'] = $app['id'] ?? 'app_' . ($index + 1); // Генерируем ID если нет
        $applications_with_ids[$app['id']] = $app;
    }
    
    update_option('bazaar_applications_cache', $applications_with_ids);
    
    return $applications_with_ids;
}

// Функция отображения заявок
function display_applications_from_api() {
    $applications = get_applications_from_backend();
    
    if (empty($applications)) {
        echo '<div class="no-applications">';
        echo '<h3>📭 Нет заявок на модерацию</h3>';
        echo '<p>Заявки появятся здесь когда пользователи будут создавать объявления</p>';
        echo '<p><strong>API Endpoint:</strong><br>';
        echo '<code>GET ' . BAZAAR_API_BASE_URL . '/ads/admin/moderation</code></p>';
        echo '</div>';
        return;
    }
    
    echo '<div class="bazaar-applications">';
    
    foreach ($applications as $application_id => $application) {
        $title = esc_html($application['title'] ?? '');
        $seller_name = esc_html($application['sellerName'] ?? 'Не указано');
        $description = wp_kses_post($application['description'] ?? '');
        $price = esc_html($application['price'] ?? '0');
        $condition = esc_html($application['condition'] ?? 'Не указано');
        $phone = esc_html($application['phone'] ?? 'Не указано');
        $photos = $application['photos'] ?? [];
        $equipment_item_id = esc_html($application['equipmentItemId'] ?? '');
        $attributes = $application['attributes'] ?? [];
        ?>
        
        <div class="application-card" data-application-id="<?php echo $application_id; ?>">
            <!-- Заголовок и цена -->
            <div class="application-header">
                <div>
                    <h2 class="application-title"><?php echo $title; ?></h2>
                    <div class="application-meta">
                        <span class="seller-name">👤 <?php echo $seller_name; ?></span>
                        <span class="application-phone">📞 <?php echo $phone; ?></span>
                        <span class="application-condition">Состояние: <?php echo $condition; ?></span>
                        <?php if ($equipment_item_id): ?>
                        <span>Категория ID: <?php echo $equipment_item_id; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="application-price"><?php echo number_format($price, 0, ',', ' '); ?> ₽</div>
            </div>
            
            <!-- Галерея изображений -->
            <?php if (!empty($photos) && is_array($photos)): ?>
            <div class="application-images">
                <?php foreach ($photos as $index => $photo_url): 
                    $clean_url = esc_url($photo_url);
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
                    <strong>Основная информация:</strong>
                    <div>Продавец: <?php echo $seller_name; ?></div>
                    <div>Телефон: <?php echo $phone; ?></div>
                    <div>Состояние: <?php echo $condition; ?></div>
                    <div>Категория ID: <?php echo $equipment_item_id; ?></div>
                </div>
                
                <div class="application-detail">
                    <strong>Атрибуты товара:</strong>
                    <div class="attributes-list">
                        <?php foreach ($attributes as $attr): ?>
                            <div class="attribute-item">
                                Атрибут <?php echo esc_html($attr['attributeId'] ?? ''); ?>: 
                                <?php 
                                if (is_array($attr['value'] ?? null)) {
                                    echo 'min: ' . esc_html($attr['value']['min'] ?? '') . ', max: ' . esc_html($attr['value']['max'] ?? '');
                                } else {
                                    echo esc_html($attr['value'] ?? '');
                                }
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Описание -->
            <?php if (!empty($description)): ?>
            <div class="application-description">
                <strong>Описание товара:</strong><br>
                <?php echo wpautop($description); ?>
            </div>
            <?php endif; ?>
            
            <!-- Кнопки действий -->
            <div class="application-actions">
                <button type="button" class="button button-primary approve-btn">✅ Одобрить</button>
                <button type="button" class="button button-secondary reject-btn">❌ Отклонить</button>
                
                <!-- Поле для причины отказа -->
                <div class="rejection-reason">
                    <strong>Причина отказа:</strong>
                    <textarea class="rejection-reason-text" placeholder="Укажите причину отказа..."></textarea>
                    <div class="action-buttons">
                        <button type="button" class="button button-secondary cancel-reject">Отмена</button>
                        <button type="button" class="button button-primary confirm-reject">Подтвердить отклонение</button>
                    </div>
                </div>
                
                <span style="margin-left: auto; color: #666; font-size: 13px;">
                    <span class="status-pending">⏳ На модерации</span>
                </span>
            </div>
        </div>
        
        <?php
    }
    
    echo '</div>';
}
?>