<?php
// articles-constructor.php

// Конфигурация API
define('ARTICLES_API_BASE_URL', 'http://host.docker.internal:3001/v1/api/admin/articles');
define('ARTICLES_API_TIMEOUT', 30);

// Загрузка медиабиблиотеки WordPress
add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'articles-constructor') !== false) {
        wp_enqueue_media();
        wp_enqueue_editor();
    }
});

// Добавляем страницу конструктора статей
add_action('admin_menu', function() {
    add_menu_page(
        'Конструктор статей',
        'Статьи',
        'manage_options',
        'articles-constructor',
        'render_articles_constructor_page',
        'dashicons-media-document',
        26
    );
    
    add_submenu_page(
        'articles-constructor',
        'Создать статью',
        'Создать статью',
        'manage_options',
        'articles-constructor-create',
        'render_create_article_page'
    );
    
    add_submenu_page(
        'articles-constructor',
        'Редактировать статью',
        'Редактировать статью',
        'manage_options',
        'articles-constructor-edit',
        'render_edit_article_page'
    );
});

function render_articles_constructor_page() {
    // Вывод сообщений
    if (isset($_GET['message'])) {
        $messages = [
            'created' => ['type' => 'success', 'text' => 'Статья успешно создана!'],
            'updated' => ['type' => 'success', 'text' => 'Статья успешно обновлена!'],
            'deleted' => ['type' => 'success', 'text' => 'Статья успешно удалена!']
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
        <h1>Конструктор статей</h1>
        <div class="notice notice-info">
            <p>Используйте конструктор статей для создания и редактирования контента на основном сайте.</p>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <div class="card" style="padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; text-align: center;">
                <h3>Создать статью</h3>
                <p>Создайте новую статью с произвольной структурой блоков</p>
                <a href="<?php echo admin_url('admin.php?page=articles-constructor-create'); ?>" class="button button-primary">Перейти к созданию</a>
            </div>
            <div class="card" style="padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; text-align: center;">
                <h3>Редактировать статью</h3>
                <p>Загрузите существующую статью для редактирования или удаления</p>
                <a href="<?php echo admin_url('admin.php?page=articles-constructor-edit'); ?>" class="button button-primary">Перейти к редактированию</a>
            </div>
        </div>
    </div>
    <?php
}

function render_create_article_page() {
    // Вывод ошибок
    if (isset($_GET['error'])) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(urldecode($_GET['error'])) . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Создание статьи</h1>
        <div class="notice notice-info">
            <p>Заполните форму для создания новой статьи. Статья будет отправлена на основной сайт.</p>
        </div>

        <div class="article-form-container">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="create-article-form" class="article-form">
                <input type="hidden" name="action" value="create_article">
                <?php wp_nonce_field('create_article_action'); ?>
                
                <div class="form-section">
                    <h3>Основная информация</h3>
                    <div class="form-group">
                        <label for="article-title">Название статьи*:</label>
                        <input type="text" id="article-title" name="title" required placeholder="Введите название статьи">
                    </div>
                    <div class="form-group">
                        <label>Слаг статьи (автогенерация)*:</label>
                        <div class="slug-preview" id="slug-preview">Введите название выше</div>
                        <input type="hidden" id="article-slug" name="slug" required>
                        <p class="description" style="font-size: 12px; color: #666; margin-top: 5px;">
                            *Слаг генерируется автоматически из названия. Минимальная длина - 3 символа.
                        </p>
                    </div>
                    <div class="form-group">
                        <label for="article-description">Краткое описание:</label>
                        <textarea id="article-description" name="description" rows="3" placeholder="Краткое описание статьи"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="banner-image">Баннерное изображение:</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="url" id="banner-image" name="banner_image" placeholder="https://example.com/banner.jpg">
                            <button type="button" class="button banner-upload-button" data-target="#banner-image">Загрузить</button>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Блоки статьи*</h3>
                    <p class="description">Добавьте необходимое количество блоков для статьи. Заголовок блока обязателен.</p>
                    
                    <div id="blocks-container">
                        <div class="block-repeater" data-block-index="1">
                            <div class="block-header">
                                <span class="block-title">Блок #1*</span>
                                <button type="button" class="remove-block button button-small">Удалить блок</button>
                            </div>
                            <div class="form-group">
                                <label>Заголовок блока*:</label>
                                <input type="text" name="blocks[1][heading]" placeholder="Введите заголовок блока" required>
                            </div>
                            <div class="form-group">
                                <label>Подзаголовок:</label>
                                <input type="text" name="blocks[1][subheading]" placeholder="Введите подзаголовок">
                            </div>
                            <div class="form-group">
                                <label>Текст блока:</label>
                                <?php 
                                wp_editor('', 'block_content_1', [
                                    'textarea_name' => 'blocks[1][content]',
                                    'textarea_rows' => 10,
                                    'media_buttons' => true,
                                    'teeny' => false,
                                    'quicktags' => true,
                                    'editor_class' => 'article-block-editor'
                                ]); 
                                ?>
                            </div>
                            <div class="form-group">
                                <label>Изображение блока:</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="url" class="image-url-input" name="blocks[1][image]" placeholder="https://example.com/image.jpg">
                                    <button type="button" class="button image-upload-button">Загрузить</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" id="add-block" class="button button-secondary">+ Добавить блок</button>
                    <p class="description" style="font-size: 12px; color: #666; margin-top: 10px;">
                        *Обязательно должен быть хотя бы один блок с заполненным заголовком
                    </p>
                </div>
                
                <div class="form-section">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="article-published" name="isPublished" value="1" checked>
                            Опубликовать статью сразу
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary button-large">Отправить статью</button>
                    <a href="<?php echo admin_url('admin.php?page=articles-constructor'); ?>" class="button">Отмена</a>
                </div>
            </form>
        </div>
    </div>
    
    <style>
    .article-form-container {
        max-width: 900px;
        margin-top: 20px;
    }
    .article-form {
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
    .slug-preview {
        background: #f6f7f7;
        padding: 10px 12px;
        border-radius: 4px;
        border: 1px solid #dcdcde;
        font-family: monospace;
        color: #3c434a;
    }
    .block-repeater {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .block-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e0e0e0;
    }
    .block-title {
        font-weight: 600;
        font-size: 16px;
    }
    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #f0f0f0;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        let blockCount = 1;
        
        // Генерация слага
        $('#article-title').on('input', function() {
            const title = $(this).val();
            if (title) {
                const generatedSlug = generateSlug(title);
                $('#slug-preview').text(generatedSlug);
                $('#article-slug').val(generatedSlug);
            } else {
                $('#slug-preview').text('Введите название выше');
                $('#article-slug').val('');
            }
        });
        
        // Функция генерации слага на JavaScript
        function generateSlug(text) {
            const map = {
                'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd',
                'е': 'e', 'ё': 'e', 'ж': 'zh', 'з': 'z', 'и': 'i',
                'й': 'j', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n',
                'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't',
                'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'c', 'ч': 'ch',
                'ш': 'sh', 'щ': 'shh', 'ъ': '', 'ы': 'y', 'ь': '',
                'э': 'e', 'ю': 'ju', 'я': 'ja'
            };
            
            let slug = text.toLowerCase();
            
            // Транслитерация
            slug = slug.split('').map(function(char) {
                return map[char] || char;
            }).join('');
            
            // Замена пробелов и удаление символов
            slug = slug.replace(/\s+/g, '-')
                       .replace(/[^\w\-]+/g, '')
                       .replace(/\-\-+/g, '-')
                       .replace(/^-+/, '')
                       .replace(/-+$/, '');
            
            // Проверка минимальной длины
            if (slug.length < 3) {
                slug = slug + '-art';
            }
            
            return slug;
        }
        
        // Инициализация загрузчиков изображений
        function initImageUploaders() {
            $('.image-upload-button').off('click').on('click', function(e) {
                e.preventDefault();
                const button = $(this);
                const input = button.siblings('.image-url-input');
                
                // Создаем медиафрейм
                const frame = wp.media({
                    title: 'Выберите изображение',
                    button: { text: 'Использовать' },
                    multiple: false
                });
                
                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    input.val(attachment.url).trigger('change');
                });
                
                frame.open();
            });
            
            // Для баннерного изображения
            $('.banner-upload-button').off('click').on('click', function(e) {
                e.preventDefault();
                const target = $(this).data('target');
                const input = $(target);
                
                const frame = wp.media({
                    title: 'Выберите баннерное изображение',
                    button: { text: 'Использовать' },
                    multiple: false
                });
                
                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    input.val(attachment.url).trigger('change');
                });
                
                frame.open();
            });
        }
        
        // Инициализация загрузчиков при загрузке страницы
        initImageUploaders();
        
        // Добавление блока
        $('#add-block').on('click', function() {
            blockCount++;
            addBlock(blockCount);
        });
        
        // Удаление блока
        $(document).on('click', '.remove-block', function() {
            if ($('.block-repeater').length > 1) {
                $(this).closest('.block-repeater').remove();
                updateBlockNumbers();
            } else {
                alert('Должен остаться хотя бы один блок');
            }
        });
        
        function addBlock(index) {
            const blockId = 'block_' + Date.now() + '_' + index;
            const blockHtml = `
                <div class="block-repeater" data-block-index="${index}">
                    <div class="block-header">
                        <span class="block-title">Блок #${index}*</span>
                        <button type="button" class="remove-block button button-small">Удалить блок</button>
                    </div>
                    <div class="form-group">
                        <label>Заголовок блока*:</label>
                        <input type="text" name="blocks[${index}][heading]" placeholder="Введите заголовок блока" required>
                    </div>
                    <div class="form-group">
                        <label>Подзаголовок:</label>
                        <input type="text" name="blocks[${index}][subheading]" placeholder="Введите подзаголовок">
                    </div>
                    <div class="form-group">
                        <label>Текст блока:</label>
                        <textarea id="${blockId}" name="blocks[${index}][content]" rows="10" style="width: 100%;"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Изображение блока:</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="url" class="image-url-input" name="blocks[${index}][image]" placeholder="https://example.com/image.jpg">
                            <button type="button" class="button image-upload-button">Загрузить</button>
                        </div>
                    </div>
                </div>
            `;
            $('#blocks-container').append(blockHtml);
            
            // Инициализируем редактор
            setTimeout(function() {
                if (typeof tinymce !== 'undefined') {
                    tinymce.init({
                        selector: '#' + blockId,
                        height: 300,
                        menubar: true,
                        plugins: 'lists link image media code',
                        toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | code',
                        setup: function(editor) {
                            editor.on('change', function() {
                                editor.save();
                            });
                        }
                    });
                }
                
                // Инициализируем загрузчик изображений для нового блока
                initImageUploaders();
            }, 100);
        }
        
        function updateBlockNumbers() {
            $('.block-repeater').each(function(index) {
                const newIndex = index + 1;
                $(this).attr('data-block-index', newIndex);
                $(this).find('.block-title').text('Блок #' + newIndex + '*');
                
                // Обновляем names инпутов
                $(this).find('[name]').each(function() {
                    const name = $(this).attr('name');
                    if (name && name.includes('blocks[')) {
                        const newName = name.replace(/blocks\[\d+\]/, `blocks[${newIndex}]`);
                        $(this).attr('name', newName);
                    }
                });
            });
            blockCount = $('.block-repeater').length;
        }
        
        // Валидация формы перед отправкой
        $('#create-article-form').on('submit', function(e) {
            const slug = $('#article-slug').val();
            if (!slug || slug.length < 3) {
                e.preventDefault();
                alert('Слаг статьи должен содержать минимум 3 символа. Пожалуйста, введите название статьи.');
                $('#article-title').focus();
                return false;
            }
            
            // Проверяем, что хотя бы один блок имеет заголовок
            let hasValidBlock = false;
            $('input[name^="blocks["][name$="[heading]"]').each(function() {
                if ($(this).val().trim().length > 0) {
                    hasValidBlock = true;
                }
            });
            
            if (!hasValidBlock) {
                e.preventDefault();
                alert('Добавьте хотя бы один блок с заполненным заголовком.');
                return false;
            }
            
            return true;
        });
    });
    </script>
    <?php
}

// Страница редактирования
function render_edit_article_page() {
    $article_data = null;
    $slug = '';
    
    // Вывод ошибок
    if (isset($_GET['error'])) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(urldecode($_GET['error'])) . '</p></div>';
    }
    
    // Загрузка статьи
    if (isset($_GET['slug']) && !empty($_GET['slug'])) {
        $slug = sanitize_text_field($_GET['slug']);
        $result = get_article_from_api($slug);
        if ($result['success']) {
            $article_data = $result['data'];
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>Редактирование статьи</h1>
        
        <div class="notice notice-info">
            <p>Введите слаг существующей статьи для загрузки и редактирования.</p>
        </div>

        <div class="article-form-container">
            <div class="article-form">
                <div class="form-section">
                    <h3>Загрузка статьи</h3>
                    <form method="get" action="<?php echo admin_url('admin.php'); ?>">
                        <input type="hidden" name="page" value="articles-constructor-edit">
                        <div style="display: flex; gap: 10px; align-items: flex-end;">
                            <div class="form-group" style="flex: 1;">
                                <label for="article-slug-input">Слаг статьи:</label>
                                <input type="text" id="article-slug-input" name="slug" value="<?php echo esc_attr($slug); ?>" placeholder="nazvanie-stati" required>
                            </div>
                            <button type="submit" class="button button-primary">Загрузить статью</button>
                        </div>
                    </form>
                </div>
                
                <?php if ($article_data): ?>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="edit-article-form">
                    <input type="hidden" name="action" value="update_article">
                    <input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>">
                    <?php wp_nonce_field('update_article_action'); ?>
                    
                    <div class="form-section">
                        <h3>Основная информация</h3>
                        <div class="form-group">
                            <label for="edit-title">Название статьи*:</label>
                            <input type="text" id="edit-title" name="title" value="<?php echo esc_attr($article_data['title']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Слаг статьи:</label>
                            <div class="slug-preview"><?php echo esc_html($article_data['slug']); ?></div>
                            <input type="hidden" name="slug" value="<?php echo esc_attr($article_data['slug']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="edit-description">Краткое описание:</label>
                            <textarea id="edit-description" name="description" rows="3"><?php echo esc_textarea($article_data['description']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit-banner-image">Баннерное изображение:</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="url" id="edit-banner-image" name="banner_image" value="<?php echo esc_attr($article_data['banner_image']); ?>">
                                <button type="button" class="button banner-upload-button" data-target="#edit-banner-image">Загрузить</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Блоки статьи*</h3>
                        <div id="edit-blocks-container">
                            <?php if (!empty($article_data['blocks'])): ?>
                                <?php foreach ($article_data['blocks'] as $index => $block): ?>
                                    <div class="block-repeater" data-block-index="<?php echo $index + 1; ?>">
                                        <div class="block-header">
                                            <span class="block-title">Блок #<?php echo $index + 1; ?>*</span>
                                            <button type="button" class="remove-edit-block button button-small">Удалить блок</button>
                                        </div>
                                        <div class="form-group">
                                            <label>Заголовок блока*:</label>
                                            <input type="text" name="blocks[<?php echo $index + 1; ?>][heading]" value="<?php echo esc_attr($block['heading']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Подзаголовок:</label>
                                            <input type="text" name="blocks[<?php echo $index + 1; ?>][subheading]" value="<?php echo esc_attr($block['subheading']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Текст блока:</label>
                                            <?php 
                                            wp_editor($block['content'], 'edit_block_' . ($index + 1), [
                                                'textarea_name' => 'blocks[' . ($index + 1) . '][content]',
                                                'textarea_rows' => 10,
                                                'media_buttons' => true,
                                                'teeny' => false,
                                                'quicktags' => true
                                            ]);
                                            ?>
                                        </div>
                                        <div class="form-group">
                                            <label>Изображение блока:</label>
                                            <div style="display: flex; gap: 10px;">
                                                <input type="url" class="image-url-input" name="blocks[<?php echo $index + 1; ?>][image]" value="<?php echo esc_attr($block['image']); ?>">
                                                <button type="button" class="button image-upload-button">Загрузить</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="edit-add-block" class="button button-secondary">+ Добавить блок</button>
                        <p class="description" style="font-size: 12px; color: #666; margin-top: 10px;">
                            *Обязательно должен быть хотя бы один блок с заполненным заголовком
                        </p>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="isPublished" value="1" <?php checked($article_data['isPublished'], true); ?>>
                                Статья опубликована
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="button button-primary">Обновить статью</button>
                        <button type="button" id="delete-article" class="button" style="background: #d63638; color: white;">Удалить статью</button>
                        <a href="<?php echo admin_url('admin.php?page=articles-constructor-edit'); ?>" class="button">Отмена</a>
                    </div>
                </form>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="delete-form" style="display: none;">
                    <input type="hidden" name="action" value="delete_article">
                    <input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>">
                    <?php wp_nonce_field('delete_article_action'); ?>
                </form>
                
                <script>
                jQuery(document).ready(function($) {
                    // Инициализация загрузчиков изображений
                    function initEditImageUploaders() {
                        $('.image-upload-button').off('click').on('click', function(e) {
                            e.preventDefault();
                            const button = $(this);
                            const input = button.siblings('.image-url-input');
                            
                            const frame = wp.media({
                                title: 'Выберите изображение',
                                button: { text: 'Использовать' },
                                multiple: false
                            });
                            
                            frame.on('select', function() {
                                const attachment = frame.state().get('selection').first().toJSON();
                                input.val(attachment.url).trigger('change');
                            });
                            
                            frame.open();
                        });
                        
                        $('.banner-upload-button').off('click').on('click', function(e) {
                            e.preventDefault();
                            const target = $(this).data('target');
                            const input = $(target);
                            
                            const frame = wp.media({
                                title: 'Выберите баннерное изображение',
                                button: { text: 'Использовать' },
                                multiple: false
                            });
                            
                            frame.on('select', function() {
                                const attachment = frame.state().get('selection').first().toJSON();
                                input.val(attachment.url).trigger('change');
                            });
                            
                            frame.open();
                        });
                    }
                    
                    initEditImageUploaders();
                    
                    // Удаление блока
                    $(document).on('click', '.remove-edit-block', function() {
                        if ($('.block-repeater').length > 1) {
                            $(this).closest('.block-repeater').remove();
                            updateEditBlockNumbers();
                        }
                    });
                    
                    // Добавление блока
                    let editBlockCount = <?php echo !empty($article_data['blocks']) ? count($article_data['blocks']) : 0; ?>;
                    
                    $('#edit-add-block').on('click', function() {
                        editBlockCount++;
                        addEditBlock(editBlockCount);
                    });
                    
                    function addEditBlock(index) {
                        const blockId = 'edit_block_' + Date.now() + '_' + index;
                        const blockHtml = `
                            <div class="block-repeater" data-block-index="${index}">
                                <div class="block-header">
                                    <span class="block-title">Блок #${index}*</span>
                                    <button type="button" class="remove-edit-block button button-small">Удалить блок</button>
                                </div>
                                <div class="form-group">
                                    <label>Заголовок блока*:</label>
                                    <input type="text" name="blocks[${index}][heading]" placeholder="Введите заголовок блока" required>
                                </div>
                                <div class="form-group">
                                    <label>Подзаголовок:</label>
                                    <input type="text" name="blocks[${index}][subheading]" placeholder="Введите подзаголовок">
                                </div>
                                <div class="form-group">
                                    <label>Текст блока:</label>
                                    <textarea id="${blockId}" name="blocks[${index}][content]" rows="10" style="width: 100%;"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Изображение блока:</label>
                                    <div style="display: flex; gap: 10px;">
                                        <input type="url" class="image-url-input" name="blocks[${index}][image]" placeholder="https://example.com/image.jpg">
                                        <button type="button" class="button image-upload-button">Загрузить</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        $('#edit-blocks-container').append(blockHtml);
                        
                        setTimeout(function() {
                            if (typeof tinymce !== 'undefined') {
                                tinymce.init({
                                    selector: '#' + blockId,
                                    height: 300,
                                    menubar: true,
                                    plugins: 'lists link image media code',
                                    toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | code',
                                    setup: function(editor) {
                                        editor.on('change', function() {
                                            editor.save();
                                        });
                                    }
                                });
                            }
                            initEditImageUploaders();
                        }, 100);
                    }
                    
                    // Удаление статьи
                    $('#delete-article').on('click', function() {
                        if (confirm('Вы уверены, что хотите удалить статью? Это действие нельзя отменить.')) {
                            $('#delete-form').submit();
                        }
                    });
                    
                    function updateEditBlockNumbers() {
                        $('#edit-blocks-container .block-repeater').each(function(index) {
                            const newIndex = index + 1;
                            $(this).attr('data-block-index', newIndex);
                            $(this).find('.block-title').text('Блок #' + newIndex + '*');
                            
                            $(this).find('[name]').each(function() {
                                const name = $(this).attr('name');
                                if (name && name.includes('blocks[')) {
                                    const newName = name.replace(/blocks\[\d+\]/, `blocks[${newIndex}]`);
                                    $(this).attr('name', newName);
                                }
                            });
                        });
                        editBlockCount = $('#edit-blocks-container .block-repeater').length;
                    }
                    
                    // Валидация формы редактирования
                    $('#edit-article-form').on('submit', function(e) {
                        // Проверяем, что хотя бы один блок имеет заголовок
                        let hasValidBlock = false;
                        $('input[name^="blocks["][name$="[heading]"]').each(function() {
                            if ($(this).val().trim().length > 0) {
                                hasValidBlock = true;
                            }
                        });
                        
                        if (!hasValidBlock) {
                            e.preventDefault();
                            alert('Добавьте хотя бы один блок с заполненным заголовком.');
                            return false;
                        }
                        
                        return true;
                    });
                });
                </script>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

// Обработчики форм
add_action('admin_post_create_article', 'handle_create_article');
function handle_create_article() {
    check_admin_referer('create_article_action');
    
    // ВАЛИДАЦИЯ СЛАГА
    if (empty($_POST['slug']) || strlen($_POST['slug']) < 3) {
        wp_redirect(admin_url('admin.php?page=articles-constructor-create&error=Слаг должен содержать минимум 3 символа'));
        exit;
    }
    
    // ВАЛИДАЦИЯ БЛОКОВ
    $valid_blocks = [];
    if (!empty($_POST['blocks']) && is_array($_POST['blocks'])) {
        foreach ($_POST['blocks'] as $index => $block) {
            // Пропускаем пустые блоки
            if (empty(trim($block['heading'])) && empty(trim($block['content']))) {
                continue;
            }
            
            // Проверяем обязательные поля
            if (empty(trim($block['heading']))) {
                wp_redirect(admin_url('admin.php?page=articles-constructor-create&error=Заголовок блока #' . ($index+1) . ' не может быть пустым'));
                exit;
            }
            
            $valid_blocks[] = [
                'heading' => sanitize_text_field($block['heading']),
                'subheading' => !empty(trim($block['subheading'])) ? sanitize_text_field($block['subheading']) : ' ',
                'content' => wp_kses_post($block['content']),
                'image' => esc_url_raw($block['image'])
            ];
        }
    }
    
    // Проверяем, что есть хотя бы один блок
    if (empty($valid_blocks)) {
        wp_redirect(admin_url('admin.php?page=articles-constructor-create&error=Добавьте хотя бы один блок статьи с заголовком'));
        exit;
    }
    
    $article_data = [
        'title' => sanitize_text_field($_POST['title']),
        'slug' => sanitize_text_field($_POST['slug']),
        'description' => sanitize_textarea_field($_POST['description']),
        'banner_image' => esc_url_raw($_POST['banner_image']),
        'isPublished' => isset($_POST['isPublished']),
        'blocks' => $valid_blocks
    ];
    
    $result = send_article_to_api($article_data, 'create');
    
    if ($result['success']) {
        wp_redirect(admin_url('admin.php?page=articles-constructor&message=created'));
    } else {
        wp_redirect(admin_url('admin.php?page=articles-constructor-create&error=' . urlencode($result['message'])));
    }
    exit;
}

add_action('admin_post_update_article', 'handle_update_article');
function handle_update_article() {
    check_admin_referer('update_article_action');
    
    // ВАЛИДАЦИЯ СЛАГА
    if (empty($_POST['slug']) || strlen($_POST['slug']) < 3) {
        wp_redirect(admin_url('admin.php?page=articles-constructor-edit&slug=' . $_POST['slug'] . '&error=Слаг должен содержать минимум 3 символа'));
        exit;
    }
    
    // ВАЛИДАЦИЯ БЛОКОВ
    $valid_blocks = [];
    if (!empty($_POST['blocks']) && is_array($_POST['blocks'])) {
        foreach ($_POST['blocks'] as $index => $block) {
            // Пропускаем пустые блоки
            if (empty(trim($block['heading'])) && empty(trim($block['content']))) {
                continue;
            }
            
            // Проверяем обязательные поля
            if (empty(trim($block['heading']))) {
                wp_redirect(admin_url('admin.php?page=articles-constructor-edit&slug=' . $_POST['slug'] . '&error=Заголовок блока #' . ($index+1) . ' не может быть пустым'));
                exit;
            }
            
            $valid_blocks[] = [
                'heading' => sanitize_text_field($block['heading']),
                'subheading' => !empty(trim($block['subheading'])) ? sanitize_text_field($block['subheading']) : ' ',
                'content' => wp_kses_post($block['content']),
                'image' => esc_url_raw($block['image'])
            ];
        }
    }
    
    // Проверяем, что есть хотя бы один блок
    if (empty($valid_blocks)) {
        wp_redirect(admin_url('admin.php?page=articles-constructor-edit&slug=' . $_POST['slug'] . '&error=Добавьте хотя бы один блок статьи с заголовком'));
        exit;
    }
    
    $article_data = [
        'title' => sanitize_text_field($_POST['title']),
        'slug' => sanitize_text_field($_POST['slug']),
        'description' => sanitize_textarea_field($_POST['description']),
        'banner_image' => esc_url_raw($_POST['banner_image']),
        'isPublished' => isset($_POST['isPublished']),
        'blocks' => $valid_blocks
    ];
    
    $result = send_article_to_api($article_data, 'update');
    
    if ($result['success']) {
        wp_redirect(admin_url('admin.php?page=articles-constructor&message=updated'));
    } else {
        wp_redirect(admin_url('admin.php?page=articles-constructor-edit&slug=' . $_POST['slug'] . '&error=' . urlencode($result['message'])));
    }
    exit;
}

add_action('admin_post_delete_article', 'handle_delete_article');
function handle_delete_article() {
    check_admin_referer('delete_article_action');
    
    $slug = sanitize_text_field($_POST['slug']);
    $result = delete_article_from_api($slug);
    
    if ($result['success']) {
        wp_redirect(admin_url('admin.php?page=articles-constructor&message=deleted'));
    } else {
        wp_redirect(admin_url('admin.php?page=articles-constructor-edit&slug=' . $slug . '&error=' . urlencode($result['message'])));
    }
    exit;
}

// AJAX для слага (оставляем для совместимости, но не используем)
add_action('wp_ajax_generate_article_slug', 'generate_article_slug_ajax');
function generate_article_slug_ajax() {
    check_ajax_referer('article_slug_nonce');
    
    $title = sanitize_text_field($_POST['title']);
    $slug = SlugGenerator::generate($title);
    
    wp_send_json_success(['slug' => $slug]);
}

// ===== API ФУНКЦИИ =====

// Отправка статьи на API
function send_article_to_api($article_data, $action) {
    $url = ARTICLES_API_BASE_URL;
    
    if ($action === 'update') {
        $url .= '/' . urlencode($article_data['slug']) . '/update';
        $method = 'PUT';
    } else {
        $url .= '/create';
        $method = 'POST';
    }
    
    error_log("Sending article to: {$url}");
    
    $response = wp_remote_request($url, [
        'method' => $method,
        'timeout' => ARTICLES_API_TIMEOUT,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($article_data)
    ]);
    
    if (is_wp_error($response)) {
        return ['success' => false, 'message' => 'Ошибка сети: ' . $response->get_error_message()];
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status_code >= 200 && $status_code < 300) {
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => "HTTP {$status_code}: " . $body];
    }
}

// Получение статьи с API
function get_article_from_api($slug) {
    $url = ARTICLES_API_BASE_URL . '/' . urlencode($slug);
    
    $response = wp_remote_get($url, [
        'timeout' => ARTICLES_API_TIMEOUT,
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
        return ['success' => true, 'data' => $data];
    } elseif ($status_code === 404) {
        return ['success' => false, 'message' => 'Статья не найдена'];
    } else {
        return ['success' => false, 'message' => "Ошибка API: HTTP {$status_code}"];
    }
}

// Удаление статьи через API
function delete_article_from_api($slug) {
    $url = ARTICLES_API_BASE_URL . '/' . urlencode($slug) . '/delete';
    
    $response = wp_remote_request($url, [
        'method' => 'DELETE',
        'timeout' => ARTICLES_API_TIMEOUT,
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

// Класс генератора слагов
class SlugGenerator {
    public static function generate(string $text): string {
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'shh', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'ju', 'я' => 'ja',
            
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
            'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'Ch',
            'Ш' => 'Sh', 'Щ' => 'Shh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'Ju', 'Я' => 'Ja'
        ];

        $transliterated = strtr($text, $map);
        $transliterated = mb_strtolower($transliterated, 'UTF-8');
        $transliterated = preg_replace('/\s+/', '-', $transliterated);
        $transliterated = preg_replace('/[.,\[\]]/', '', $transliterated);
        
        return $transliterated;
    }
}