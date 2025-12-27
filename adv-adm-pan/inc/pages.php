<?php
// texts-manager.php

// Конфигурация API
define('TEXTS_API_BASE_URL', 'http://host.docker.internal:3001/v1/api/admin/texts');
define('TEXTS_API_TIMEOUT', 30);

// Добавляем страницу управления текстовками
add_action('admin_menu', function() {
    add_menu_page(
        'Изменение наполнения страниц',
        'Содержание страниц',
        'manage_options',
        'texts-manager',
        'render_texts_manager_page',
        'dashicons-edit',
        28
    );
});

function render_texts_manager_page() {
    $slug = isset($_GET['slug']) ? sanitize_text_field($_GET['slug']) : '';
    $texts_data = null;
    $page_info = null;
    
    // Загрузка текстов по слагу
    if (!empty($slug)) {
        $result = get_texts_from_api($slug);
        if ($result['success']) {
            $texts_data = $result['data'];
            $page_info = $result['page_info'] ?? null;
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }
    
    // Обработка успешного обновления
    if (isset($_GET['message']) && $_GET['message'] === 'updated') {
        echo '<div class="notice notice-success is-dismissible"><p>Текст успешно обновлен!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Управление текстами страниц</h1>
        
        <div class="notice notice-info">
            <p>Введите слаг страницы для загрузки и редактирования текстовых блоков.</p>
        </div>

        <div class="texts-form-container">
            <div class="texts-form">
                <div class="form-section">
                    <h3>Загрузка текстов</h3>
                    <form method="get" action="<?php echo admin_url('admin.php'); ?>">
                        <input type="hidden" name="page" value="texts-manager">
                        <div style="display: flex; gap: 10px; align-items: flex-end;">
                            <div class="form-group" style="flex: 1;">
                                <label for="page-slug-input">Слаг страницы*:</label>
                                <input type="text" id="page-slug-input" name="slug" value="<?php echo esc_attr($slug); ?>" placeholder="main-page или about-us" required>
                                <p class="description" style="font-size: 12px; color: #666; margin-top: 5px;">
                                    Например: main, about, contacts, products и т.д.
                                </p>
                            </div>
                            <button type="submit" class="button button-primary">Загрузить тексты</button>
                        </div>
                    </form>
                </div>
                
                <?php if (!empty($slug)): ?>
                    <?php if ($page_info): ?>
                    <div class="page-info-card" style="background: linear-gradient(135deg, #f0f6fc 0%, #ffffff 100%); border-left: 4px solid #2271b1; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                        <h3 style="margin-top: 0; color: #2271b1;">Информация о странице</h3>
                        <div style="display: grid; grid-template-columns: auto 1fr; gap: 10px 20px;">
                            <div style="font-weight: 600;">Слаг:</div>
                            <div><code><?php echo esc_html($page_info['slug'] ?? $slug); ?></code></div>
                            
                            <div style="font-weight: 600;">Название:</div>
                            <div><?php echo esc_html($page_info['name'] ?? 'N/A'); ?></div>
                            
                            <?php if (isset($page_info['description'])): ?>
                            <div style="font-weight: 600;">Описание:</div>
                            <div><?php echo esc_html($page_info['description']); ?></div>
                            <?php endif; ?>
                            
                            <?php if (isset($page_info['texts_count'])): ?>
                            <div style="font-weight: 600;">Текстовых блоков:</div>
                            <div><?php echo intval($page_info['texts_count']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-section">
                        <h3>Текстовые блоки 
                            <span class="badge" style="background: #2271b1; color: white; padding: 2px 8px; border-radius: 12px; font-size: 14px;">
                                <?php echo is_array($texts_data) ? count($texts_data) : 0; ?> блоков
                            </span>
                        </h3>
                        
                        <?php if (is_array($texts_data) && !empty($texts_data)): ?>
                            <div class="texts-list">
                                <?php foreach ($texts_data as $text_block): ?>
                                    <div class="text-block-card" id="text-block-<?php echo esc_attr($text_block['id']); ?>" data-id="<?php echo esc_attr($text_block['id']); ?>">
                                        <div class="text-block-header">
                                            <div class="text-block-info">
                                                <div class="text-block-key">
                                                    <code><?php echo esc_html($text_block['key']); ?></code>
                                                </div>
                                                <div class="text-block-meta">
                                                    <span class="text-block-id">ID: <?php echo esc_html($text_block['id']); ?></span>
                                                    <span class="text-block-updated">
                                                        Обновлено: <?php echo date('d.m.Y H:i', strtotime($text_block['updatedAt'])); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="text-block-actions">
                                                <button type="button" class="button button-small edit-text-btn" data-id="<?php echo esc_attr($text_block['id']); ?>">
                                                    <span class="dashicons dashicons-edit"></span> Изменить
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="text-block-content">
                                            <div class="text-block-view" id="text-view-<?php echo esc_attr($text_block['id']); ?>">
                                                <div class="text-block-value">
                                                    <?php echo nl2br(esc_html($text_block['value'])); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="text-block-edit" id="text-edit-<?php echo esc_attr($text_block['id']); ?>" style="display: none;">
                                                <div class="form-group">
                                                    <label for="text-value-<?php echo esc_attr($text_block['id']); ?>">Текст:</label>
                                                    <textarea id="text-value-<?php echo esc_attr($text_block['id']); ?>" 
                                                              class="text-value-input" 
                                                              rows="6"
                                                              data-original-value="<?php echo esc_attr($text_block['value']); ?>"><?php echo esc_textarea($text_block['value']); ?></textarea>
                                                    <div class="char-count" style="font-size: 12px; color: #666; margin-top: 5px; text-align: right;">
                                                        Символов: <span id="char-count-<?php echo esc_attr($text_block['id']); ?>">0</span>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($text_block['description'])): ?>
                                                <div class="text-block-description" style="font-size: 13px; color: #666; margin-top: 5px; font-style: italic;">
                                                    <strong>Описание:</strong> <?php echo esc_html($text_block['description']); ?>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="text-block-edit-actions">
                                                    <button type="button" class="button button-primary apply-text-btn" data-id="<?php echo esc_attr($text_block['id']); ?>" disabled>
                                                        <span class="dashicons dashicons-yes"></span> Применить
                                                    </button>
                                                    <button type="button" class="button cancel-text-btn" data-id="<?php echo esc_attr($text_block['id']); ?>">
                                                        <span class="dashicons dashicons-no"></span> Отмена
                                                    </button>
                                                    <span class="spinner" id="spinner-<?php echo esc_attr($text_block['id']); ?>" style="float: none;"></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-block-status" id="status-<?php echo esc_attr($text_block['id']); ?>"></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif (empty($texts_data)): ?>
                            <div class="no-texts-message" style="text-align: center; padding: 40px; background: #f6f7f7; border-radius: 8px; border: 2px dashed #ddd;">
                                <span class="dashicons dashicons-warning" style="font-size: 48px; color: #a7aaad; margin-bottom: 20px; display: block;"></span>
                                <h3 style="color: #646970; margin-bottom: 10px;">Тексты не найдены</h3>
                                <p style="color: #8c8f94;">Для страницы со слагом "<strong><?php echo esc_html($slug); ?></strong>" тексты не найдены.</p>
                                <p style="color: #8c8f94; margin-top: 10px;">Проверьте правильность написания слага.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="help-card" style="background: #f6f7f7; border-radius: 8px; padding: 25px; margin-top: 20px;">
                        <h3 style="margin-top: 0; color: #23282d;">Как это работает?</h3>
                        <ul style="list-style-type: disc; padding-left: 20px; color: #646970;">
                            <li>Введите слаг страницы в поле выше (например: main, about, contacts)</li>
                            <li>Система загрузит все текстовые блоки для этой страницы</li>
                            <li>Для редактирования текста нажмите кнопку "Изменить" на нужном блоке</li>
                            <li>Текст станет редактируемым в текстовом поле</li>
                            <li>После изменения нажмите "Применить" для сохранения</li>
                            <li>Изменения сохраняются сразу через AJAX</li>
                        </ul>
                        
                        <h4 style="margin-top: 20px; color: #23282d;">Примеры слагов:</h4>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                            <span class="slug-example" style="background: #e8f4fd; color: #2271b1; padding: 5px 12px; border-radius: 4px; font-family: monospace; font-size: 13px;">
                                main
                            </span>
                            <span class="slug-example" style="background: #e8f4fd; color: #2271b1; padding: 5px 12px; border-radius: 4px; font-family: monospace; font-size: 13px;">
                                about
                            </span>
                            <span class="slug-example" style="background: #e8f4fd; color: #2271b1; padding: 5px 12px; border-radius: 4px; font-family: monospace; font-size: 13px;">
                                contacts
                            </span>
                            <span class="slug-example" style="background: #e8f4fd; color: #2271b1; padding: 5px 12px; border-radius: 4px; font-family: monospace; font-size: 13px;">
                                products
                            </span>
                            <span class="slug-example" style="background: #e8f4fd; color: #2271b1; padding: 5px 12px; border-radius: 4px; font-family: monospace; font-size: 13px;">
                                services
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
    .texts-form-container {
        max-width: 1000px;
        margin-top: 20px;
    }
    
    .texts-form {
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
    
    input[type="text"] {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }
    
    /* Стили для карточек текстовых блоков */
    .texts-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .text-block-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    
    .text-block-card.editing {
        border-color: #2271b1;
        box-shadow: 0 4px 12px rgba(34, 113, 177, 0.1);
        background: #f8fbff;
    }
    
    .text-block-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .text-block-info {
        flex: 1;
    }
    
    .text-block-key {
        font-family: 'Courier New', monospace;
        font-size: 15px;
        color: #2271b1;
        font-weight: 600;
        margin-bottom: 5px;
        word-break: break-all;
    }
    
    .text-block-meta {
        display: flex;
        gap: 15px;
        font-size: 13px;
        color: #646970;
    }
    
    .text-block-actions .button {
        transition: all 0.2s;
    }
    
    .text-block-content {
        margin-bottom: 15px;
    }
    
    .text-block-value {
        color: #23282d;
        line-height: 1.6;
        font-size: 15px;
        word-wrap: break-word;
    }
    
    .text-block-edit {
        margin-top: 15px;
    }
    
    .text-value-input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        font-size: 14px;
        line-height: 1.6;
        resize: vertical;
        box-sizing: border-box;
    }
    
    .text-value-input:focus {
        border-color: #2271b1;
        box-shadow: 0 0 0 1px #2271b1;
        outline: none;
    }
    
    .text-block-edit-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-top: 15px;
    }
    
    .text-block-status {
        min-height: 22px;
        font-size: 13px;
        padding: 5px 0;
    }
    
    .status-success {
        color: #00a32a;
    }
    
    .status-error {
        color: #d63638;
    }
    
    .status-loading {
        color: #2271b1;
    }
    
    .spinner {
        visibility: hidden;
        margin: 0;
    }
    
    .spinner.is-active {
        visibility: visible;
    }
    
    /* Стили для ключей в коде */
    code {
        background: #f6f7f7;
        padding: 2px 6px;
        border-radius: 3px;
        font-family: 'Courier New', monospace;
        font-size: 13px;
    }
    
    /* Адаптивность */
    @media (max-width: 768px) {
        .text-block-header {
            flex-direction: column;
            gap: 15px;
        }
        
        .text-block-meta {
            flex-direction: column;
            gap: 5px;
        }
        
        .text-block-actions {
            width: 100%;
        }
        
        .text-block-actions .button {
            width: 100%;
        }
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Создаем объект textsManager прямо в JavaScript
        const textsManager = {
            ajaxurl: '<?php echo admin_url("admin-ajax.php"); ?>',
            nonce: '<?php echo wp_create_nonce("texts_manager_nonce"); ?>'
        };
        
        console.log('textsManager initialized:', textsManager);
        
        // Автофокус на поле слага
        $('#page-slug-input').focus();
        
        // Подсчет символов в textarea при загрузке
        $('.text-value-input').each(function() {
            const textarea = $(this);
            const textId = textarea.closest('.text-block-card').data('id');
            const charCount = $('#char-count-' + textId);
            charCount.text(textarea.val().length);
        });
        
        // Обработчик ввода в textarea
        $(document).on('input', '.text-value-input', function() {
            const textarea = $(this);
            const textId = textarea.closest('.text-block-card').data('id');
            const charCount = $('#char-count-' + textId);
            charCount.text(textarea.val().length);
            
            // Активируем/деактивируем кнопку "Применить"
            const originalValue = textarea.data('original-value');
            const applyBtn = textarea.closest('.text-block-edit').find('.apply-text-btn');
            
            if (textarea.val() !== originalValue && textarea.val().trim() !== '') {
                applyBtn.prop('disabled', false);
            } else {
                applyBtn.prop('disabled', true);
            }
        });
        
        // Обработчик кнопки "Изменить"
        $(document).on('click', '.edit-text-btn', function() {
            const textId = $(this).data('id');
            const textCard = $('#text-block-' + textId);
            const viewDiv = $('#text-view-' + textId);
            const editDiv = $('#text-edit-' + textId);
            
            // Переключаем режим редактирования
            viewDiv.hide();
            editDiv.show();
            textCard.addClass('editing');
            
            // Фокус на textarea
            $('#text-value-' + textId).focus().select();
            
            // Скрываем кнопку "Изменить"
            $(this).hide();
        });
        
        // Обработчик кнопки "Отмена"
        $(document).on('click', '.cancel-text-btn', function() {
            const textId = $(this).data('id');
            const textCard = $('#text-block-' + textId);
            const viewDiv = $('#text-view-' + textId);
            const editDiv = $('#text-edit-' + textId);
            const textarea = $('#text-value-' + textId);
            
            // Сбрасываем значение textarea к оригинальному
            textarea.val(textarea.data('original-value'));
            
            // Переключаем режим просмотра
            editDiv.hide();
            viewDiv.show();
            textCard.removeClass('editing');
            
            // Показываем кнопку "Изменить"
            textCard.find('.edit-text-btn').show();
            
            // Сбрасываем спиннер и статус
            $('#spinner-' + textId).removeClass('is-active');
            $('#status-' + textId).empty().removeClass('status-success status-error');
            
            // Сбрасываем подсчет символов
            const charCount = $('#char-count-' + textId);
            charCount.text(textarea.val().length);
        });
        
        // Обработчик кнопки "Применить"
        $(document).on('click', '.apply-text-btn', function() {
            const textId = $(this).data('id');
            const textarea = $('#text-value-' + textId);
            const newValue = textarea.val().trim();
            const originalValue = textarea.data('original-value');
            const statusDiv = $('#status-' + textId);
            const spinner = $('#spinner-' + textId);
            const applyBtn = $(this);
            
            console.log('Apply clicked for text ID:', textId);
            console.log('New value:', newValue);
            console.log('Original value:', originalValue);
            console.log('AJAX URL:', textsManager.ajaxurl);
            console.log('Nonce:', textsManager.nonce);
            
            // Валидация
            if (newValue === '') {
                statusDiv.html('<span class="status-error">Текст не может быть пустым</span>');
                return;
            }
            
            if (newValue === originalValue) {
                statusDiv.html('<span class="status-error">Текст не изменен</span>');
                return;
            }
            
            // Показываем спиннер и очищаем статус
            spinner.addClass('is-active');
            statusDiv.empty().removeClass('status-success status-error');
            
            // Блокируем кнопку на время запроса
            applyBtn.prop('disabled', true);
            applyBtn.html('<span class="dashicons dashicons-update"></span> Сохранение...');
            
            // Отправляем AJAX запрос
            $.ajax({
                url: textsManager.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'update_text_block',
                    nonce: textsManager.nonce,
                    text_id: textId,
                    value: newValue
                },
                timeout: 10000, // 10 секунд таймаут
                success: function(response) {
                    console.log('AJAX success response:', response);
                    spinner.removeClass('is-active');
                    applyBtn.html('<span class="dashicons dashicons-yes"></span> Применить');
                    
                    if (response.success) {
                        console.log('Update successful');
                        // Обновляем оригинальное значение
                        textarea.data('original-value', newValue);
                        
                        // Обновляем отображаемый текст
                        $('#text-view-' + textId + ' .text-block-value').html(newValue.replace(/\n/g, '<br>'));
                        
                        // Показываем успех
                        statusDiv.html('<span class="status-success">✓ Текст успешно обновлен</span>');
                        
                        // Деактивируем кнопку "Применить" (ждем изменений)
                        applyBtn.prop('disabled', true);
                        
                        // Обновляем дату в мета-информации (если есть в ответе)
                        if (response.data && response.data.updatedAt) {
                            const date = new Date(response.data.updatedAt);
                            const formattedDate = date.toLocaleDateString('ru-RU') + ' ' + date.toLocaleTimeString('ru-RU', {hour: '2-digit', minute:'2-digit'});
                            $('#text-block-' + textId + ' .text-block-updated').text('Обновлено: ' + formattedDate);
                        }
                        
                        // Автоматически возвращаем в режим просмотра через 2 секунды
                        setTimeout(function() {
                            const editDiv = $('#text-edit-' + textId);
                            const viewDiv = $('#text-view-' + textId);
                            const textCard = $('#text-block-' + textId);
                            
                            if (editDiv.is(':visible')) {
                                editDiv.hide();
                                viewDiv.show();
                                textCard.removeClass('editing');
                                textCard.find('.edit-text-btn').show();
                                statusDiv.empty();
                            }
                        }, 2000);
                        
                    } else {
                        console.log('Update failed:', response.data);
                        const errorMsg = response.data || 'Неизвестная ошибка';
                        statusDiv.html('<span class="status-error">✗ Ошибка: ' + errorMsg + '</span>');
                        // Разблокируем кнопку при ошибке
                        applyBtn.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error:', status, error);
                    console.log('XHR response:', xhr.responseText);
                    spinner.removeClass('is-active');
                    applyBtn.html('<span class="dashicons dashicons-yes"></span> Применить');
                    
                    let errorMessage = 'Ошибка сети';
                    if (status === 'timeout') {
                        errorMessage = 'Таймаут запроса. Проверьте соединение с API.';
                    } else if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.data || 'Ошибка сервера: ' + xhr.status;
                        } catch(e) {
                            errorMessage = 'Ошибка сервера: ' + xhr.status;
                        }
                    }
                    
                    statusDiv.html('<span class="status-error">✗ ' + errorMessage + '</span>');
                    // Разблокируем кнопку при ошибке
                    applyBtn.prop('disabled', false);
                }
            });
        });
        
        // Анимация появления карточек
        $('.text-block-card').each(function(index) {
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
        
        // Обработка нажатия Enter в textarea (Ctrl+Enter для сохранения)
        $(document).on('keydown', '.text-value-input', function(e) {
            if (e.key === 'Enter' && e.ctrlKey) {
                e.preventDefault();
                $(this).closest('.text-block-edit').find('.apply-text-btn').click();
            }
        });
    });
    </script>
    <?php
}

// ===== API ФУНКЦИИ =====

// Получение текстов по слагу
function get_texts_from_api($slug) {
    $url = TEXTS_API_BASE_URL . '/' . urlencode($slug);
    
    error_log("Getting texts from API: {$url}");
    
    $response = wp_remote_get($url, [
        'timeout' => TEXTS_API_TIMEOUT,
        'headers' => [
            'Content-Type' => 'application/json',
        ]
    ]);
    
    if (is_wp_error($response)) {
        error_log("Texts API error: " . $response->get_error_message());
        return ['success' => false, 'message' => 'Ошибка сети: ' . $response->get_error_message()];
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    error_log("Texts API response: {$status_code}, Body length: " . strlen($body));
    
    if ($status_code === 200 && is_array($data)) {
        $page_info = [
            'slug' => $slug,
            'texts_count' => count($data)
        ];
        
        return ['success' => true, 'data' => $data, 'page_info' => $page_info];
    } elseif ($status_code === 404) {
        return ['success' => false, 'message' => 'Страница с таким слагом не найдена'];
    } else {
        return ['success' => false, 'message' => "Ошибка API: HTTP {$status_code}. Ответ: " . substr($body, 0, 200)];
    }
}

// Обновление текстового блока
function update_text_block_api($text_id, $value) {
    $url = TEXTS_API_BASE_URL . '/' . urlencode($text_id) . '/update';
    
    $request_data = [
        'value' => $value
    ];
    
    error_log("Updating text via API: {$url}");
    error_log("Request data: " . json_encode($request_data));
    
    $response = wp_remote_request($url, [
        'method' => 'PUT',
        'timeout' => TEXTS_API_TIMEOUT,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($request_data)
    ]);
    
    if (is_wp_error($response)) {
        error_log("Update API error: " . $response->get_error_message());
        return ['success' => false, 'message' => 'Ошибка сети: ' . $response->get_error_message()];
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    error_log("Update API response: {$status_code}, Body: " . $body);
    
    if ($status_code >= 200 && $status_code < 300) {
        $data = json_decode($body, true);
        return ['success' => true, 'data' => $data];
    } else {
        $error_msg = "HTTP {$status_code}";
        if (!empty($body)) {
            $error_data = json_decode($body, true);
            if (is_array($error_data) && isset($error_data['message'])) {
                $error_msg = $error_data['message'];
            } else {
                $error_msg .= ": " . substr($body, 0, 200);
            }
        }
        return ['success' => false, 'message' => $error_msg];
    }
}

// AJAX обработчик для обновления текста
add_action('wp_ajax_update_text_block', 'handle_update_text_block');
function handle_update_text_block() {
    error_log("=== AJAX HANDLER STARTED ===");
    
    // Проверка nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'texts_manager_nonce')) {
        error_log("Nonce verification failed");
        wp_send_json_error('Security check failed');
        wp_die();
    }
    
    // Проверка прав
    if (!current_user_can('manage_options')) {
        error_log("User doesn't have manage_options capability");
        wp_send_json_error('Insufficient permissions');
        wp_die();
    }
    
    // Валидация данных
    $text_id = isset($_POST['text_id']) ? intval($_POST['text_id']) : 0;
    $value = isset($_POST['value']) ? sanitize_textarea_field($_POST['value']) : '';
    
    error_log("Text ID: {$text_id}, Value length: " . strlen($value));
    
    if ($text_id <= 0) {
        error_log("Invalid text ID: {$text_id}");
        wp_send_json_error('Invalid text ID');
        wp_die();
    }
    
    if (empty(trim($value))) {
        error_log("Empty value for text ID: {$text_id}");
        wp_send_json_error('Text cannot be empty');
        wp_die();
    }
    
    // Обновление через API
    $result = update_text_block_api($text_id, $value);
    
    if ($result['success']) {
        error_log("Update successful for text ID: {$text_id}");
        wp_send_json_success($result['data']);
    } else {
        error_log("Update failed for text ID {$text_id}: " . $result['message']);
        wp_send_json_error($result['message']);
    }
    
    error_log("=== AJAX HANDLER FINISHED ===");
    wp_die();
}