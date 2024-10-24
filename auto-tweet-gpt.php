<?php
/*
Plugin Name: Auto Tweet GPT-4 Bot
Description: GPT-4を使ってX（Twitter）に自動でツイートするWordPressプラグイン
Version: 1.4
Author: あなたの名前
*/

if (!defined('ABSPATH')) {
    exit; // 直接アクセスを防ぐ
}

// Composerのオートロードを読み込む
require_once __DIR__ . '/vendor/autoload.php';

// TwitterOAuthの名前空間をインポート
use Abraham\TwitterOAuth\TwitterOAuth;

// プラグイン設定ページの追加
function auto_tweet_gpt_menu() {
    add_options_page(
        'Auto Tweet GPT-4 設定',
        'Auto Tweet GPT-4',
        'manage_options',
        'auto-tweet-gpt-4',
        'auto_tweet_gpt_settings_page'
    );
}
add_action('admin_menu', 'auto_tweet_gpt_menu');

// 設定ページのHTML
function auto_tweet_gpt_settings_page() {
    ?>
    <div class="wrap">
        <h1>Auto Tweet GPT-4 設定</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('auto_tweet_gpt_options');
            do_settings_sections('auto-tweet-gpt-4');
            submit_button();
            ?>
        </form>

        <h2>ツイート履歴</h2>
        <?php auto_tweet_gpt_display_tweet_log(); ?>

        <form method="post">
            <input type="hidden" name="clear_tweet_log" value="1" />
            <button type="submit" class="button button-secondary">ツイート履歴を削除</button>
        </form>

        <form method="post">
            <input type="hidden" name="test_tweet" value="1" />
            <button type="submit" class="button button-primary">テスト投稿</button>
        </form>
    </div>
    <?php
}

// 設定の登録
function auto_tweet_gpt_register_settings() {
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_prompts');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_execution_mode');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_hashtags');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_frequency');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_openai_key');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_twitter_key');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_twitter_secret');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_access_token');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_access_secret');
}
add_action('admin_init', 'auto_tweet_gpt_register_settings');

// 設定セクションとフィールドの作成
function auto_tweet_gpt_settings_fields() {
    add_settings_section(
        'auto_tweet_gpt_main_section',
        '主要設定',
        null,
        'auto-tweet-gpt-4'
    );

    add_settings_field(
        'auto_tweet_gpt_prompts',
        '問い合わせ内容（各行ごとに入力）',
        'auto_tweet_gpt_prompts_field',
        'auto-tweet-gpt-4',
        'auto_tweet_gpt_main_section'
    );

    add_settings_field(
        'auto_tweet_gpt_execution_mode',
        '実行方法',
        'auto_tweet_gpt_execution_mode_field',
        'auto-tweet-gpt-4',
        'auto_tweet_gpt_main_section'
    );

    add_settings_field(
        'auto_tweet_gpt_hashtags',
        'ハッシュタグ（カンマ区切りで指定）',
        'auto_tweet_gpt_hashtags_field',
        'auto-tweet-gpt-4',
        'auto_tweet_gpt_main_section'
    );

    add_settings_field(
        'auto_tweet_gpt_frequency',
        '実行頻度（分単位）',
        'auto_tweet_gpt_frequency_field',
        'auto-tweet-gpt-4',
        'auto_tweet_gpt_main_section'
    );

    add_settings_field(
        'auto_tweet_gpt_openai_key',
        'OpenAI APIキー',
        'auto_tweet_gpt_openai_key_field',
        'auto-tweet-gpt-4',
        'auto_tweet_gpt_main_section'
    );

    add_settings_field(
        'auto_tweet_gpt_twitter_key',
        'Twitter APIキー',
        'auto_tweet_gpt_twitter_key_field',
        'auto-tweet-gpt-4',
        'auto_tweet_gpt_main_section'
    );

    add_settings_field(
        'auto_tweet_gpt_twitter_secret',
        'Twitter APIシークレットキー',
        'auto_tweet_gpt_twitter_secret_field',
        'auto-tweet-gpt-4',
        'auto_tweet_gpt_main_section'
    );

    add_settings_field(
        'auto_tweet_gpt_access_token',
        'Access Token',
        'auto_tweet_gpt_access_token_field',
        'auto-tweet-gpt-4',
        'auto_tweet_gpt_main_section'
    );

    add_settings_field(
        'auto_tweet_gpt_access_secret',
        'Access Secret',
        'auto_tweet_gpt_access_secret_field',
        'auto-tweet-gpt-4',
        'auto_tweet_gpt_main_section'
    );
}
add_action('admin_init', 'auto_tweet_gpt_settings_fields');

// 各フィールドのHTML
function auto_tweet_gpt_prompts_field() {
    $value = get_option('auto_tweet_gpt_prompts', '');
    echo "<textarea name='auto_tweet_gpt_prompts' rows='5' cols='100'>" . esc_textarea($value) . "</textarea>";
}

function auto_tweet_gpt_execution_mode_field() {
    $value = get_option('auto_tweet_gpt_execution_mode', 'random');
    ?>
    <select name="auto_tweet_gpt_execution_mode">
        <option value="random" <?php selected($value, 'random'); ?>>ランダム</option>
        <option value="sequential" <?php selected($value, 'sequential'); ?>>順番</option>
    </select>
    <?php
}

function auto_tweet_gpt_hashtags_field() {
    $value = get_option('auto_tweet_gpt_hashtags', '');
    echo "<input type='text' name='auto_tweet_gpt_hashtags' value='" . esc_attr($value) . "' class='regular-text' />";
}

function auto_tweet_gpt_frequency_field() {
    $value = get_option('auto_tweet_gpt_frequency', 60);
    echo "<input type='number' name='auto_tweet_gpt_frequency' value='" . esc_attr($value) . "' class='small-text' /> 分";
}

function auto_tweet_gpt_openai_key_field() {
    $value = get_option('auto_tweet_gpt_openai_key', '');
    echo "<input type='text' name='auto_tweet_gpt_openai_key' value='" . esc_attr($value) . "' class='regular-text' />";
}

function auto_tweet_gpt_twitter_key_field() {
    $value = get_option('auto_tweet_gpt_twitter_key', '');
    echo "<input type='text' name='auto_tweet_gpt_twitter_key' value='" . esc_attr($value) . "' class='regular-text' />";
}

function auto_tweet_gpt_twitter_secret_field() {
    $value = get_option('auto_tweet_gpt_twitter_secret', '');
    echo "<input type='text' name='auto_tweet_gpt_twitter_secret' value='" . esc_attr($value) . "' class='regular-text' />";
}

function auto_tweet_gpt_access_token_field() {
    $value = get_option('auto_tweet_gpt_access_token', '');
    echo "<input type='text' name='auto_tweet_gpt_access_token' value='" . esc_attr($value) . "' class='regular-text' />";
}

function auto_tweet_gpt_access_secret_field() {
    $value = get_option('auto_tweet_gpt_access_secret', '');
    echo "<input type='text' name='auto_tweet_gpt_access_secret' value='" . esc_attr($value) . "' class='regular-text' />";
}


// ツイートログを表示する関数
function auto_tweet_gpt_display_tweet_log() {
    $tweet_log = get_option('auto_tweet_gpt_tweet_log', array());
    $tweet_log = array_reverse($tweet_log); // 最新順に表示

    if (empty($tweet_log)) {
        echo '<p>ツイート履歴はありません。</p>';
    } else {
        echo '<ul>';
        foreach (array_slice($tweet_log, 0, 50) as $log) {
            echo '<li><strong>' . esc_html($log['time']) . '</strong>: ' . esc_html($log['content']) . '</li>';
        }
        echo '</ul>';
    }
}

// ツイートの即時投稿処理
add_action('admin_init', function() {
    if (isset($_POST['test_tweet'])) {
        auto_tweet_gpt_execute();
        wp_redirect(admin_url('options-general.php?page=auto-tweet-gpt-4'));
        exit;
    }
});

// ツイートログを保存する関数
function auto_tweet_gpt_save_tweet_log($content) {
    $tweet_log = get_option('auto_tweet_gpt_tweet_log', array());

    $tweet_log[] = array(
        'time'    => current_time('Y-m-d H:i:s'),
        'content' => $content,
    );

    // 50件以上の履歴があれば古いものを削除
    if (count($tweet_log) > 50) {
        array_shift($tweet_log);
    }

    update_option('auto_tweet_gpt_tweet_log', $tweet_log);
}

// ツイートログを削除する処理
if (isset($_POST['clear_tweet_log'])) {
    update_option('auto_tweet_gpt_tweet_log', array());
    wp_redirect(admin_url('options-general.php?page=auto-tweet-gpt-4'));
    exit;
}

// 定期実行のスケジュール設定
function auto_tweet_gpt_schedule() {
    if (wp_next_scheduled('auto_tweet_gpt_event')) {
        wp_clear_scheduled_hook('auto_tweet_gpt_event');
    }

    $frequency = (int)get_option('auto_tweet_gpt_frequency', 60);
    wp_schedule_event(time(), 'auto_tweet_gpt_custom_interval', 'auto_tweet_gpt_event');
}

add_filter('cron_schedules', function($schedules) {
    $frequency = (int)get_option('auto_tweet_gpt_frequency', 60);
    $schedules['auto_tweet_gpt_custom_interval'] = array(
        'interval' => $frequency * 60,
        'display'  => __('Custom Interval')
    );
    return $schedules;
});

// プロンプト（問い合わせ内容）を取得する関数
function auto_tweet_gpt_get_prompt() {
    $prompts = explode("\n", get_option('auto_tweet_gpt_prompts', ''));
    $mode = get_option('auto_tweet_gpt_execution_mode', 'random');

    // 空白の行を取り除く
    $prompts = array_filter(array_map('trim', $prompts));

    if (empty($prompts)) {
        return '今日のためになる情報を教えてください。';
    }

    if ($mode === 'random') {
        return $prompts[array_rand($prompts)]; // ランダムに選択
    } else {
        $index = get_option('auto_tweet_gpt_last_index', 0) % count($prompts);
        update_option('auto_tweet_gpt_last_index', $index + 1);
        return $prompts[$index]; // 順番に選択
    }
}

// GPT-4を使った問い合わせとツイート送信
function auto_tweet_gpt_execute() {
    $prompt = auto_tweet_gpt_get_prompt();
    $openai_key = get_option('auto_tweet_gpt_openai_key', '');
    $twitter_key = get_option('auto_tweet_gpt_twitter_key', '');
    $twitter_secret = get_option('auto_tweet_gpt_twitter_secret', '');
    $access_token = get_option('auto_tweet_gpt_access_token', '');
    $access_secret = get_option('auto_tweet_gpt_access_secret', '');
    
    // 各種キーが取得できているか確認
    error_log('API Key: ' . $twitter_key);
    error_log('API Secret: ' . $twitter_secret);
    error_log('Access Token: ' . $access_token);
    error_log('Access Secret: ' . $access_secret);
    if (empty($twitter_key) || empty($twitter_secret) || empty($access_token) || empty($access_secret)) {
        error_log('Twitter API キーが未設定です');
        return;
    }
    
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $openai_key,
            'Content-Type'  => 'application/json',
        ),
        'body' => json_encode(array(
            'model'    => 'gpt-4o',
            'messages' => array(
                array('role' => 'system', 'content' => $prompt)
            ),
        )),
    ));

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $content = $data['choices'][0]['message']['content'] ?? '';

    $hashtags = get_option('auto_tweet_gpt_hashtags', '');
    $tweet = mb_substr($content, 0, 140 - mb_strlen($hashtags) - 1) . ' ' . $hashtags;

    $connection = new TwitterOAuth($twitter_key, $twitter_secret, $access_token, $access_secret);
    if (!$connection) {
        error_log('TwitterOAuthの initialize error');
        exit;
    }
    // error_log('connection : ' . var_export($connection, true));

    $rate_limit = $connection->get('application/rate_limit_status');
    error_log('rete limit : ' . var_export($rate_limit, true));

    $tweet = rawurlencode($tweet); // ツイート内容をURLエンコード
    $result = $connection->post('statuses/update', parameters: ['status' => $tweet]);

    $status_code = $connection->getLastHttpCode(); // HTTPステータスコードを取得
    if ($status_code == 200) {
        auto_tweet_gpt_save_tweet_log($tweet); // ツイートをログに保存
        error_log('Tweet OK: ' . $tweet);
    } else {
        error_log('Tweet NG (HTTP): ' . $status_code);
        error_log('Twitter API Response: ' . var_export($result, true));
        }
}
add_action('auto_tweet_gpt_event', 'auto_tweet_gpt_execute');

// プラグイン有効化時のスケジュール設定
register_activation_hook(__FILE__, 'auto_tweet_gpt_schedule');

// プラグイン無効化時のスケジュール解除
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('auto_tweet_gpt_event');
});


