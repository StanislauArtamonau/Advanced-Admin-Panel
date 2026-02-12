<?php
// portfolio-manager.php

// Конфигурация API
define('PORTFOLIO_API_BASE_URL', 'http://host.docker.internal:3001/v1/api/admin/portfolio/cases');
define('PORTFOLIO_API_TIMEOUT', 30);

// Загрузка скриптов и стилей
add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'portfolio-manager') !== false) {
        wp_enqueue_media(); // Для медиабиблиотеки
        wp_enqueue_style('portfolio-manager-style', false);
        wp_enqueue_script('portfolio-manager-script', false, ['jquery', 'jquery-ui-sortable'], null, true);
        
        // Для сортировки
        wp_enqueue_script('jquery-ui-sortable');
        
        // Локализация для AJAX
        wp_localize_script('portfolio-manager-script', 'portfolioManager', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('portfolio_manager_nonce'),
            'max_photos' => 30
        ]);
    }
});

// Добавляем страницу управления портфолио
add_action('admin_menu', function() {
    add_menu_page(
        'Управление портфолио',
        'Портфолио',
        'manage_options',
        'portfolio-manager',
        'render_portfolio_manager_page',
        'dashicons-portfolio',
        29
    );
});

function render_portfolio_manager_page() {
    // Получаем параметры
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
    $year_filter = isset($_GET['year']) ? intval($_GET['year']) : '';
    
    // Загружаем кейсы
    $result = get_cases_from_api([
        'page' => $current_page,
        'limit' => 5,
        'search' => $search_query,
        'status' => $status_filter !== 'all' ? $status_filter : null,
        'year' => $year_filter ?: null
    ]);
    
    $cases_data = $result['success'] ? $result['data'] : [];
    $total_pages = $result['total_pages'] ?? 1;
    $total_items = $result['total_items'] ?? 0;
    
    // Получаем уникальные годы для фильтра
    $years = get_unique_years_from_cases($cases_data);
    ?>
    <div class="wrap">
        <h1>Управление портфолио</h1>
        
        <?php if (isset($_GET['message'])): ?>
            <div class="notice notice-<?php echo $_GET['message'] === 'deleted' ? 'warning' : 'success'; ?> is-dismissible">
                <p>
                    <?php if ($_GET['message'] === 'created'): ?>
                        Кейс успешно создан!
                    <?php elseif ($_GET['message'] === 'updated'): ?>
                        Кейс успешно обновлен!
                    <?php elseif ($_GET['message'] === 'deleted'): ?>
                        Кейс успешно удален!
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html(urldecode($_GET['error'])); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="portfolio-header">
            <div class="portfolio-actions">
                <button type="button" class="button button-primary" id="add-case-btn">
                    <span class="dashicons dashicons-plus"></span> Добавить кейс
                </button>
            </div>
            
            <div class="portfolio-filters">
                <form method="get" action="<?php echo admin_url('admin.php'); ?>">
                    <input type="hidden" name="page" value="portfolio-manager">
                    
                    <div class="filter-group">
                        <input type="text" 
                               name="search" 
                               placeholder="Поиск по названию..." 
                               value="<?php echo esc_attr($search_query); ?>"
                               class="filter-search">
                    </div>
                    
                    <div class="filter-group">
                        <select name="status" class="filter-select">
                            <option value="all" <?php selected($status_filter, 'all'); ?>>Все статусы</option>
                            <option value="published" <?php selected($status_filter, 'published'); ?>>Опубликованные</option>
                            <option value="draft" <?php selected($status_filter, 'draft'); ?>>Черновики</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select name="year" class="filter-select">
                            <option value="">Все годы</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo esc_attr($year); ?>" <?php selected($year_filter, $year); ?>>
                                    <?php echo esc_html($year); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="button">Применить</button>
                        <?php if ($search_query || $status_filter !== 'all' || $year_filter): ?>
                            <a href="<?php echo admin_url('admin.php?page=portfolio-manager'); ?>" class="button">
                                Сбросить
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="portfolio-container">
            <?php if ($result['success'] && !empty($cases_data)): ?>
                <div class="cases-list" id="cases-list">
                    <?php foreach ($cases_data as $case): ?>
                        <div class="case-card" data-id="<?php echo esc_attr($case['id']); ?>" data-order="<?php echo esc_attr($case['order'] ?? 0); ?>">
                            <div class="case-card-header">
                                <div class="case-card-info">
                                    <div class="case-company">
                                        <h3><?php echo esc_html($case['companyName']); ?></h3>
                                        <span class="case-id">ID: <?php echo esc_html($case['id']); ?></span>
                                    </div>
                                    
                                    <div class="case-meta">
                                        <?php if (!empty($case['year'])): ?>
                                            <span class="case-year"><?php echo esc_html($case['year']); ?> год</span>
                                        <?php endif; ?>
                                        
                                        <span class="case-photos-count">
                                            <span class="dashicons dashicons-camera"></span>
                                            <?php echo !empty($case['photos']) ? count($case['photos']) : 0; ?>/30
                                        </span>
                                        
                                        <span class="case-status <?php echo $case['isPublished'] ? 'published' : 'draft'; ?>">
                                            <?php if ($case['isPublished']): ?>
                                                <span class="dashicons dashicons-visibility"></span> Опубликован
                                            <?php else: ?>
                                                <span class="dashicons dashicons-hidden"></span> Черновик
                                            <?php endif; ?>
                                        </span>
                                        
                                        <span class="case-order">
                                            Порядок: <span class="order-value"><?php echo esc_html($case['order'] ?? 0); ?></span>
                                        </span>
                                    </div>
                                    
                                    <?php if (!empty($case['tags'])): ?>
                                        <div class="case-tags">
                                            <?php foreach ($case['tags'] as $tag): ?>
                                                <span class="case-tag"><?php echo esc_html($tag); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="case-card-actions">
                                    <button type="button" class="button button-small toggle-status-btn" 
                                            data-id="<?php echo esc_attr($case['id']); ?>"
                                            data-status="<?php echo $case['isPublished'] ? 'published' : 'draft'; ?>">
                                        <?php if ($case['isPublished']): ?>
                                            <span class="dashicons dashicons-hidden"></span> В черновик
                                        <?php else: ?>
                                            <span class="dashicons dashicons-visibility"></span> Опубликовать
                                        <?php endif; ?>
                                    </button>
                                    
                                    <button type="button" class="button button-small edit-case-btn" 
                                            data-id="<?php echo esc_attr($case['id']); ?>">
                                        <span class="dashicons dashicons-edit"></span> Редактировать
                                    </button>
                                    
                                    <button type="button" class="button button-small delete-case-btn" 
                                            data-id="<?php echo esc_attr($case['id']); ?>"
                                            data-name="<?php echo esc_attr($case['companyName']); ?>">
                                        <span class="dashicons dashicons-trash"></span> Удалить
                                    </button>
                                </div>
                            </div>
                            
                            <?php if (!empty($case['description'])): ?>
                                <div class="case-description">
                                    <?php echo wpautop(esc_html($case['description'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($case['photos']) && is_array($case['photos'])): ?>
                                <div class="case-photos-preview">
                                    <div class="photos-grid">
                                        <?php foreach (array_slice($case['photos'], 0, 4) as $index => $photo): ?>
                                            <div class="photo-thumb" style="background-image: url('<?php echo esc_url($photo); ?>');">
                                                <?php if ($index === 3 && count($case['photos']) > 4): ?>
                                                    <div class="photo-more">+<?php echo count($case['photos']) - 4; ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="portfolio-pagination">
                        <?php
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $current_page,
                            'add_args' => [
                                'search' => $search_query,
                                'status' => $status_filter,
                                'year' => $year_filter
                            ]
                        ]);
                        ?>
                        
                        <div class="pagination-info">
                            Показано: <strong><?php echo count($cases_data); ?></strong> из <strong><?php echo $total_items; ?></strong> кейсов
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($search_query || $status_filter !== 'all' || $year_filter): ?>
                <div class="no-results">
                    <div class="no-results-icon">
                        <span class="dashicons dashicons-search"></span>
                    </div>
                    <h3>Ничего не найдено</h3>
                    <p>Попробуйте изменить параметры поиска или <a href="<?php echo admin_url('admin.php?page=portfolio-manager'); ?>">сбросить фильтры</a>.</p>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <span class="dashicons dashicons-portfolio"></span>
                    </div>
                    <h3>Портфолио пусто</h3>
                    <p>Добавьте первый кейс, чтобы начать работу с портфолио.</p>
                    <button type="button" class="button button-primary" id="add-first-case-btn">
                        <span class="dashicons dashicons-plus"></span> Добавить первый кейс
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Модальное окно для создания/редактирования -->
    <div id="case-modal" class="portfolio-modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Добавить кейс</h2>
                <button type="button" class="modal-close">&times;</button>
            </div>
            
            <form id="case-form" class="modal-form">
                <input type="hidden" id="case-id" name="id" value="">
                <input type="hidden" id="form-action" name="action" value="create">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="company-name">Название компании *</label>
                        <input type="text" id="company-name" name="companyName" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="case-year">Год</label>
                        <input type="number" id="case-year" name="year" min="2000" max="2030" step="1">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="case-description">Описание</label>
                    <?php 
                    wp_editor('', 'case-description', [
                        'textarea_name' => 'description',
                        'textarea_rows' => 8,
                        'media_buttons' => false,
                        'teeny' => true,
                        'quicktags' => false
                    ]);
                    ?>
                </div>
                
                <div class="form-group">
                    <label for="case-tags">Теги</label>
                    <input type="text" id="case-tags" name="tags" placeholder="Введите теги через запятую">
                    <div id="tags-suggestions" class="tags-suggestions"></div>
                </div>
                
                <div class="form-group">
                    <label>Фотографии (максимум 30)</label>
                    <div class="photos-upload-area" id="photos-upload-area">
                        <div class="upload-placeholder" id="upload-placeholder">
                            <span class="dashicons dashicons-format-image"></span>
                            <p>Перетащите сюда фотографии или</p>
                            <button type="button" class="button" id="select-photos-btn">Выбрать файлы</button>
                            <p class="upload-hint">Максимум 30 фотографий. Поддерживаются JPG, PNG, GIF.</p>
                        </div>
                        <div class="photos-list" id="photos-list"></div>
                    </div>
                    <input type="hidden" id="case-photos" name="photos" value="">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="case-order">Порядок отображения</label>
                        <input type="number" id="case-order" name="order" min="0" max="999" value="0">
                    </div>
                    
                    <div class="form-group form-checkbox">
                        <label>
                            <input type="checkbox" id="case-published" name="isPublished" value="1" checked>
                            Опубликован
                        </label>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="button button-primary" id="save-case-btn">
                        <span class="dashicons dashicons-yes"></span> Сохранить
                    </button>
                    <button type="button" class="button modal-cancel">Отмена</button>
                    <span class="spinner" id="modal-spinner"></span>
                </div>
            </form>
        </div>
    </div>
    
    <style>
    /* Основные стили */
    .portfolio-header {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .portfolio-actions {
        flex-shrink: 0;
    }
    
    .portfolio-filters {
        flex: 1;
        min-width: 300px;
    }
    
    .portfolio-filters form {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: flex-end;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .filter-search {
        min-width: 250px;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .filter-select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        min-width: 150px;
    }
    
    /* Карточки кейсов */
    .cases-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin: 20px 0;
    }
    
    .case-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        cursor: move;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .case-card.ui-sortable-helper {
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transform: rotate(2deg);
    }
    
    .case-card.ui-sortable-placeholder {
        background: #f6f7f7;
        border: 2px dashed #ddd;
        visibility: visible !important;
    }
    
    .case-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .case-card-info {
        flex: 1;
        min-width: 300px;
    }
    
    .case-company {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }
    
    .case-company h3 {
        margin: 0;
        font-size: 18px;
        color: #23282d;
    }
    
    .case-id {
        font-size: 12px;
        color: #646970;
        background: #f6f7f7;
        padding: 2px 6px;
        border-radius: 3px;
    }
    
    .case-meta {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        font-size: 13px;
        color: #646970;
        margin-bottom: 10px;
    }
    
    .case-meta span {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    .case-status {
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .case-status.published {
        background: #d1f7c4;
        color: #0e6245;
    }
    
    .case-status.draft {
        background: #f0f0f0;
        color: #646970;
    }
    
    .case-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    
    .case-tag {
        background: #e8f4fd;
        color: #2271b1;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
    }
    
    .case-card-actions {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    
    .case-description {
        color: #3c434a;
        line-height: 1.6;
        margin-bottom: 15px;
        max-height: 100px;
        overflow: hidden;
        position: relative;
    }
    
    .case-description:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 30px;
        background: linear-gradient(transparent, white);
    }
    
    .case-photos-preview {
        margin-top: 15px;
    }
    
    .photos-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }
    
    .photo-thumb {
        aspect-ratio: 1;
        background-size: cover;
        background-position: center;
        border-radius: 4px;
        position: relative;
        overflow: hidden;
    }
    
    .photo-more {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.7);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
    }
    
    /* Пагинация */
    .portfolio-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #f0f0f0;
    }
    
    .pagination-info {
        color: #646970;
        font-size: 14px;
    }
    
    /* Пустые состояния */
    .empty-state, .no-results {
        text-align: center;
        padding: 60px 20px;
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        margin: 40px 0;
    }
    
    .empty-state-icon, .no-results-icon {
        font-size: 64px;
        color: #a7aaad;
        margin-bottom: 20px;
    }
    
    .empty-state h3, .no-results h3 {
        color: #23282d;
        margin-bottom: 10px;
    }
    
    .empty-state p, .no-results p {
        color: #646970;
        margin-bottom: 20px;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
    
    /* Модальное окно */
    .portfolio-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
    }
    
    .modal-content {
        position: relative;
        background: white;
        width: 90%;
        max-width: 800px;
        max-height: 90vh;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 30px;
        background: #f6f7f7;
        border-bottom: 1px solid #ddd;
    }
    
    .modal-header h2 {
        margin: 0;
        font-size: 20px;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #646970;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-form {
        padding: 30px;
        overflow-y: auto;
        max-height: calc(90vh - 80px);
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
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
    
    input[type="text"],
    input[type="number"],
    input[type="url"],
    textarea,
    select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }
    
    .form-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 28px;
    }
    
    .photos-upload-area {
        border: 2px dashed #ddd;
        border-radius: 8px;
        padding: 20px;
        min-height: 150px;
        transition: border-color 0.3s;
    }
    
    .photos-upload-area.drag-over {
        border-color: #2271b1;
        background: #f8fbff;
    }
    
    .upload-placeholder {
        text-align: center;
        color: #646970;
    }
    
    .upload-placeholder .dashicons {
        font-size: 48px;
        color: #a7aaad;
        margin-bottom: 10px;
        display: block;
    }
    
    .upload-hint {
        font-size: 12px;
        color: #8c8f94;
        margin-top: 10px;
    }
    
    .photos-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 10px;
        margin-top: 20px;
    }
    
    .photo-item {
        position: relative;
        aspect-ratio: 1;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .photo-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .photo-remove {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(214, 54, 56, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        padding: 0;
    }
    
    .modal-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #f0f0f0;
    }
    
    .spinner {
        visibility: hidden;
    }
    
    .spinner.is-active {
        visibility: visible;
    }
    
    /* Адаптивность */
    @media (max-width: 768px) {
        .portfolio-header {
            flex-direction: column;
        }
        
        .portfolio-filters form {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-search, .filter-select {
            min-width: auto;
            width: 100%;
        }
        
        .case-card-header {
            flex-direction: column;
        }
        
        .case-card-info {
            min-width: auto;
        }
        
        .case-card-actions {
            width: 100%;
            justify-content: flex-start;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .photos-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Глобальные переменные
        let allTags = [];
        let currentPhotos = [];
        let mediaFrame = null;
        const maxPhotos = 30;
        
        // Инициализация сортировки
        $('#cases-list').sortable({
            handle: '.case-card',
            placeholder: 'case-card ui-sortable-placeholder',
            update: function(event, ui) {
                updateCaseOrder();
            }
        });
        
        // Обновление порядка кейсов
        function updateCaseOrder() {
            const orderData = [];
            $('.case-card').each(function(index) {
                const caseId = $(this).data('id');
                orderData.push({
                    id: caseId,
                    order: index + 1
                });
                
                // Обновляем отображаемый порядок
                $(this).find('.order-value').text(index + 1);
            });
            
            // Отправляем обновление через PATCH для каждого кейса
            orderData.forEach(item => {
                $.ajax({
                    url: portfolioManager.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'update_case_partial',
                        nonce: portfolioManager.nonce,
                        case_id: item.id,
                        order: item.order
                    }
                });
            });
        }
        
        // Кнопка добавления кейса
        $('#add-case-btn, #add-first-case-btn').on('click', function() {
            openCaseModal('create');
        });
        
        // Кнопка редактирования кейса
        $(document).on('click', '.edit-case-btn', function() {
            const caseId = $(this).data('id');
            loadCaseData(caseId);
        });
        
        // Переключение статуса
        $(document).on('click', '.toggle-status-btn', function() {
            const caseId = $(this).data('id');
            const currentStatus = $(this).data('status');
            const newStatus = currentStatus === 'published' ? 'draft' : 'published';
            const caseCard = $(this).closest('.case-card');
            
            $.ajax({
                url: portfolioManager.ajaxurl,
                type: 'POST',
                data: {
                    action: 'update_case_partial',
                    nonce: portfolioManager.nonce,
                    case_id: caseId,
                    isPublished: newStatus === 'published'
                },
                success: function(response) {
                    if (response.success) {
                        // Обновляем отображение
                        const statusSpan = caseCard.find('.case-status');
                        const statusBtn = caseCard.find('.toggle-status-btn');
                        
                        if (newStatus === 'published') {
                            statusSpan.removeClass('draft').addClass('published');
                            statusSpan.html('<span class="dashicons dashicons-visibility"></span> Опубликован');
                            statusBtn.html('<span class="dashicons dashicons-hidden"></span> В черновик');
                            statusBtn.data('status', 'published');
                        } else {
                            statusSpan.removeClass('published').addClass('draft');
                            statusSpan.html('<span class="dashicons dashicons-hidden"></span> Черновик');
                            statusBtn.html('<span class="dashicons dashicons-visibility"></span> Опубликовать');
                            statusBtn.data('status', 'draft');
                        }
                    }
                }
            });
        });
        
        // Удаление кейса
        $(document).on('click', '.delete-case-btn', function() {
            const caseId = $(this).data('id');
            const caseName = $(this).data('name');
            
            if (confirm(`Удалить кейс "${caseName}"? Это действие нельзя отменить.`)) {
                window.location.href = `<?php echo admin_url('admin.php?page=portfolio-manager&action=delete&id='); ?>${caseId}&_wpnonce=<?php echo wp_create_nonce('delete_case'); ?>`;
            }
        });
        
        // Загрузка данных кейса для редактирования
        function loadCaseData(caseId) {
            $('#modal-spinner').addClass('is-active');
            
            $.ajax({
                url: portfolioManager.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_case',
                    nonce: portfolioManager.nonce,
                    case_id: caseId
                },
                success: function(response) {
                    if (response.success) {
                        openCaseModal('edit', response.data);
                    }
                },
                complete: function() {
                    $('#modal-spinner').removeClass('is-active');
                }
            });
        }
        
        // Открытие модального окна
        function openCaseModal(action, caseData = null) {
            $('#modal-title').text(action === 'create' ? 'Добавить кейс' : 'Редактировать кейс');
            $('#form-action').val(action);
            
            if (caseData) {
                // Заполняем форму данными
                $('#case-id').val(caseData.id);
                $('#company-name').val(caseData.companyName);
                $('#case-year').val(caseData.year || '');
                $('#case-order').val(caseData.order || 0);
                $('#case-published').prop('checked', caseData.isPublished);
                
                // Описание
                if (typeof tinymce !== 'undefined' && tinymce.get('case-description')) {
                    tinymce.get('case-description').setContent(caseData.description || '');
                } else {
                    $('#case-description').val(caseData.description || '');
                }
                
                // Теги
                $('#case-tags').val(caseData.tags ? caseData.tags.join(', ') : '');
                
                // Фотографии
                currentPhotos = caseData.photos || [];
                updatePhotosList();
            } else {
                // Очищаем форму
                $('#case-form')[0].reset();
                $('#case-id').val('');
                $('#case-order').val('0');
                $('#case-published').prop('checked', true);
                
                if (typeof tinymce !== 'undefined' && tinymce.get('case-description')) {
                    tinymce.get('case-description').setContent('');
                }
                
                currentPhotos = [];
                updatePhotosList();
            }
            
            $('#case-modal').fadeIn();
        }
        
        // Закрытие модального окна
        $('.modal-close, .modal-cancel, .modal-overlay').on('click', function() {
            $('#case-modal').fadeOut();
        });
        
        // Загрузка фотографий через медиабиблиотеку
        $('#select-photos-btn').on('click', function(e) {
            e.preventDefault();
            
            if (mediaFrame) {
                mediaFrame.open();
                return;
            }
            
            mediaFrame = wp.media({
                title: 'Выберите фотографии',
                button: {
                    text: 'Использовать выбранные'
                },
                multiple: true,
                library: {
                    type: 'image'
                }
            });
            
            mediaFrame.on('select', function() {
                const attachments = mediaFrame.state().get('selection').toJSON();
                
                attachments.forEach(attachment => {
                    if (currentPhotos.length < maxPhotos) {
                        currentPhotos.push(attachment.url);
                    }
                });
                
                updatePhotosList();
            });
            
            mediaFrame.open();
        });
        
        // Drag & drop для фотографий
        $('#photos-upload-area').on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });
        
        $('#photos-upload-area').on('dragleave', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
        });
        
        $('#photos-upload-area').on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            alert('Для загрузки фотографий используйте медиабиблиотеку WordPress.');
        });
        
        // Обновление списка фотографий
        function updatePhotosList() {
            const photosList = $('#photos-list');
            const uploadPlaceholder = $('#upload-placeholder');
            
            photosList.empty();
            
            if (currentPhotos.length > 0) {
                uploadPlaceholder.hide();
                
                currentPhotos.forEach((photo, index) => {
                    photosList.append(`
                        <div class="photo-item">
                            <img src="${photo}" alt="Фото ${index + 1}">
                            <button type="button" class="photo-remove" data-index="${index}">&times;</button>
                        </div>
                    `);
                });
                
                // Показываем предупреждение если много фото
                if (currentPhotos.length >= maxPhotos) {
                    photosList.before('<p class="upload-warning" style="color: #d63638;">Достигнут лимит в 30 фотографий</p>');
                }
            } else {
                uploadPlaceholder.show();
            }
            
            // Обновляем скрытое поле
            $('#case-photos').val(JSON.stringify(currentPhotos));
        }
        
        // Удаление фотографии
        $(document).on('click', '.photo-remove', function() {
            const index = $(this).data('index');
            currentPhotos.splice(index, 1);
            updatePhotosList();
        });
        
        // Автодополнение тегов
        $('#case-tags').on('input', function() {
            const input = $(this).val();
            const lastTag = input.split(',').pop().trim();
            
            if (lastTag.length > 1) {
                // Здесь можно добавить AJAX запрос для получения существующих тегов
                // Пока просто очищаем подсказки
                $('#tags-suggestions').empty();
            }
        });
        
        // Обработка формы
        $('#case-form').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = $('#form-action').val();
            const caseId = $('#case-id').val();
            
            // Собираем данные
            const data = {
                companyName: $('#company-name').val(),
                year: $('#case-year').val() || null,
                description: typeof tinymce !== 'undefined' && tinymce.get('case-description') 
                    ? tinymce.get('case-description').getContent() 
                    : $('#case-description').val(),
                tags: $('#case-tags').val() ? $('#case-tags').val().split(',').map(tag => tag.trim()).filter(tag => tag) : [],
                photos: currentPhotos,
                order: parseInt($('#case-order').val()) || 0,
                isPublished: $('#case-published').is(':checked')
            };
            
            // Валидация
            if (!data.companyName.trim()) {
                alert('Введите название компании');
                return;
            }
            
            // Показываем спиннер
            $('#modal-spinner').addClass('is-active');
            $('#save-case-btn').prop('disabled', true);
            
            // Отправка данных
            $.ajax({
                url: portfolioManager.ajaxurl,
                type: 'POST',
                data: {
                    action: action === 'create' ? 'create_case' : 'update_case',
                    nonce: portfolioManager.nonce,
                    case_id: caseId,
                    ...data
                },
                success: function(response) {
                    if (response.success) {
                        // Закрываем модалку и перезагружаем страницу
                        $('#case-modal').fadeOut();
                        window.location.reload();
                    } else {
                        alert('Ошибка: ' + (response.data || 'Неизвестная ошибка'));
                    }
                },
                error: function() {
                    alert('Ошибка сети. Попробуйте еще раз.');
                },
                complete: function() {
                    $('#modal-spinner').removeClass('is-active');
                    $('#save-case-btn').prop('disabled', false);
                }
            });
        });
        
        // Загружаем существующие теги при загрузке страницы
        loadExistingTags();
        
        function loadExistingTags() {
            // Здесь можно добавить AJAX запрос для загрузки существующих тегов
            // Пока оставляем пустым
        }
    });
    </script>
    <?php
}

// ===== API ФУНКЦИИ =====

// Получение кейсов с фильтрацией
function get_cases_from_api($params = []) {
    $query_params = [];
    
    if (!empty($params['page'])) $query_params['page'] = $params['page'];
    if (!empty($params['limit'])) $query_params['limit'] = $params['limit'];
    if (!empty($params['search'])) $query_params['search'] = $params['search'];
    if (!empty($params['status'])) $query_params['isPublished'] = $params['status'] === 'published';
    if (!empty($params['year'])) $query_params['year'] = $params['year'];
    
    $url = PORTFOLIO_API_BASE_URL;
    if (!empty($query_params)) {
        $url .= '?' . http_build_query($query_params);
    }
    
    $response = wp_remote_get($url, [
        'timeout' => PORTFOLIO_API_TIMEOUT,
        'headers' => ['Content-Type' => 'application/json']
    ]);
    
    if (is_wp_error($response)) {
        return ['success' => false, 'message' => 'Ошибка сети: ' . $response->get_error_message()];
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($status_code === 200) {
        // Предполагаем, что API возвращает данные в формате:
        // { data: [...], total: X, page: X, limit: X, totalPages: X }
        return [
            'success' => true,
            'data' => $data['data'] ?? $data,
            'total_items' => $data['total'] ?? count($data['data'] ?? $data),
            'current_page' => $data['page'] ?? 1,
            'total_pages' => $data['totalPages'] ?? ceil(($data['total'] ?? 0) / ($params['limit'] ?? 5))
        ];
    } else {
        return ['success' => false, 'message' => "Ошибка API: HTTP {$status_code}"];
    }
}

// Получение уникальных годов из кейсов
function get_unique_years_from_cases($cases) {
    $years = [];
    foreach ($cases as $case) {
        if (!empty($case['year']) && !in_array($case['year'], $years)) {
            $years[] = $case['year'];
        }
    }
    rsort($years); // Сортируем по убыванию
    return $years;
}

// AJAX обработчики
add_action('wp_ajax_get_case', 'handle_get_case');
function handle_get_case() {
    check_ajax_referer('portfolio_manager_nonce', 'nonce');
    
    $case_id = intval($_POST['case_id']);
    
    if ($case_id <= 0) {
        wp_send_json_error('Invalid case ID');
        wp_die();
    }
    
    $url = PORTFOLIO_API_BASE_URL . '/' . $case_id;
    $response = wp_remote_get($url, [
        'timeout' => PORTFOLIO_API_TIMEOUT,
        'headers' => ['Content-Type' => 'application/json']
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error('Network error: ' . $response->get_error_message());
        wp_die();
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($status_code === 200) {
        wp_send_json_success($data);
    } else {
        wp_send_json_error("API error: HTTP {$status_code}");
    }
    
    wp_die();
}

add_action('wp_ajax_create_case', 'handle_create_case');
function handle_create_case() {
    check_ajax_referer('portfolio_manager_nonce', 'nonce');
    
    $data = [
        'companyName' => sanitize_text_field($_POST['companyName']),
        'year' => !empty($_POST['year']) ? intval($_POST['year']) : null,
        'description' => wp_kses_post($_POST['description']),
        'tags' => !empty($_POST['tags']) ? array_map('trim', explode(',', sanitize_text_field($_POST['tags']))) : [],
        'photos' => !empty($_POST['photos']) ? json_decode(stripslashes($_POST['photos']), true) : [],
        'order' => intval($_POST['order']),
        'isPublished' => !empty($_POST['isPublished'])
    ];
    
    // Валидация
    if (empty($data['companyName'])) {
        wp_send_json_error('Company name is required');
        wp_die();
    }
    
    if (count($data['photos']) > 30) {
        wp_send_json_error('Maximum 30 photos allowed');
        wp_die();
    }
    
    $response = wp_remote_post(PORTFOLIO_API_BASE_URL, [
        'timeout' => PORTFOLIO_API_TIMEOUT,
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode($data)
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error('Network error: ' . $response->get_error_message());
        wp_die();
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status_code >= 200 && $status_code < 300) {
        wp_send_json_success();
    } else {
        wp_send_json_error("API error: HTTP {$status_code} - " . $body);
    }
    
    wp_die();
}

add_action('wp_ajax_update_case', 'handle_update_case');
function handle_update_case() {
    check_ajax_referer('portfolio_manager_nonce', 'nonce');
    
    $case_id = intval($_POST['case_id']);
    
    if ($case_id <= 0) {
        wp_send_json_error('Invalid case ID');
        wp_die();
    }
    
    $data = [
        'companyName' => sanitize_text_field($_POST['companyName']),
        'year' => !empty($_POST['year']) ? intval($_POST['year']) : null,
        'description' => wp_kses_post($_POST['description']),
        'tags' => !empty($_POST['tags']) ? array_map('trim', explode(',', sanitize_text_field($_POST['tags']))) : [],
        'photos' => !empty($_POST['photos']) ? json_decode(stripslashes($_POST['photos']), true) : [],
        'order' => intval($_POST['order']),
        'isPublished' => !empty($_POST['isPublished'])
    ];
    
    $url = PORTFOLIO_API_BASE_URL . '/' . $case_id;
    $response = wp_remote_request($url, [
        'method' => 'PUT',
        'timeout' => PORTFOLIO_API_TIMEOUT,
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode($data)
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error('Network error: ' . $response->get_error_message());
        wp_die();
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status_code >= 200 && $status_code < 300) {
        wp_send_json_success();
    } else {
        wp_send_json_error("API error: HTTP {$status_code} - " . $body);
    }
    
    wp_die();
}

add_action('wp_ajax_update_case_partial', 'handle_update_case_partial');
function handle_update_case_partial() {
    check_ajax_referer('portfolio_manager_nonce', 'nonce');
    
    $case_id = intval($_POST['case_id']);
    
    if ($case_id <= 0) {
        wp_send_json_error('Invalid case ID');
        wp_die();
    }
    
    // Собираем только переданные поля
    $data = [];
    if (isset($_POST['companyName'])) $data['companyName'] = sanitize_text_field($_POST['companyName']);
    if (isset($_POST['year'])) $data['year'] = intval($_POST['year']);
    if (isset($_POST['description'])) $data['description'] = wp_kses_post($_POST['description']);
    if (isset($_POST['tags'])) $data['tags'] = array_map('trim', explode(',', sanitize_text_field($_POST['tags'])));
    if (isset($_POST['photos'])) $data['photos'] = json_decode(stripslashes($_POST['photos']), true);
    if (isset($_POST['order'])) $data['order'] = intval($_POST['order']);
    if (isset($_POST['isPublished'])) $data['isPublished'] = !empty($_POST['isPublished']);
    
    $url = PORTFOLIO_API_BASE_URL . '/' . $case_id;
    $response = wp_remote_request($url, [
        'method' => 'PATCH',
        'timeout' => PORTFOLIO_API_TIMEOUT,
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode($data)
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error('Network error: ' . $response->get_error_message());
        wp_die();
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status_code >= 200 && $status_code < 300) {
        wp_send_json_success();
    } else {
        wp_send_json_error("API error: HTTP {$status_code} - " . $body);
    }
    
    wp_die();
}

// Обработчик удаления через GET
add_action('admin_init', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'portfolio-manager' && isset($_GET['action']) && $_GET['action'] === 'delete') {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_case')) {
            wp_die('Security check failed');
        }
        
        $case_id = intval($_GET['id']);
        
        if ($case_id > 0) {
            $url = PORTFOLIO_API_BASE_URL . '/' . $case_id;
            $response = wp_remote_request($url, [
                'method' => 'DELETE',
                'timeout' => PORTFOLIO_API_TIMEOUT,
                'headers' => ['Content-Type' => 'application/json']
            ]);
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) < 300) {
                wp_redirect(admin_url('admin.php?page=portfolio-manager&message=deleted'));
            } else {
                wp_redirect(admin_url('admin.php?page=portfolio-manager&error=' . urlencode('Ошибка удаления')));
            }
            exit;
        }
    }
});
?>