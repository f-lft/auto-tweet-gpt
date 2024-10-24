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

// GPT-4を使った問い合わせとツイート送信
function auto_tweet_gpt_execute() {
    $prompt = auto_tweet_gpt_get_prompt();
    $openai_key = get_option('auto_tweet_gpt_openai_key', '');
    $twitter_key = get_option('auto_tweet_gpt_twitter_key', '');
    $twitter_secret = get_option('auto_tweet_gpt_twitter_secret', '');
    $access_token = get_option('auto_tweet_gpt_access_token', '');
    $access_secret = get_option('auto_tweet_gpt_access_secret', '');

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

    require 'vendor/autoload.php';
    use Abraham\TwitterOAuth\TwitterOAuth;

    $connection = new TwitterOAuth($twitter_key, $twitter_secret, $access_token, $access_secret);
    $result = $connection->post('statuses/update', ['status' => $tweet]);

    if ($connection->getLastHttpCode() == 200) {
        auto_tweet_gpt_save_tweet_log($tweet); // ツイートをログに保存
        error_log('ツイート成功: ' . $tweet);
    } else {
        error_log('ツイート失敗: ' . print_r($result, true));
    }
}
add_action('auto_tweet_gpt_event', 'auto_tweet_gpt_execute');

// プラグイン有効化時のスケジュール設定
register_activation_hook(__FILE__, 'auto_tweet_gpt_schedule');

// プラグイン無効化時のスケジュール解除
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('auto_tweet_gpt_event');
});
