<?php
/*
Plugin Name: Auto Tweet GPT-4 Bot
Description: GPT-4を使ってX（Twitter）に自動でツイートするWordPressプラグイン
Version: 1.4
Author: Futoshi Okazaki
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use Abraham\TwitterOAuth\TwitterOAuth;

// プラグイン設定ページの追加
function auto_tweet_gpt_menu()
{
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
function auto_tweet_gpt_settings_page()
{
?>
    <div class="wrap">
        <h1>Auto Tweet GPT-4 設定</h1>

        <?php if (get_option('auto_tweet_gpt_time_control_enabled', 1)): ?>
            <div class="notice notice-info">
                <p>📢 現在の投稿制限時間</p>
                <p>
                    <?php
                    $start = get_option('auto_tweet_gpt_quiet_start', '00:00');
                    $end = get_option('auto_tweet_gpt_quiet_end', '07:00');
                    echo sprintf(
                        '日本時間の%sから%sまでの間は自動投稿を行いません。',
                        esc_html($start),
                        esc_html($end)
                    );
                    ?>
                </p>
            </div>
        <?php endif; ?>

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
function auto_tweet_gpt_register_settings()
{
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_prompts');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_execution_mode');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_hashtags');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_frequency');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_openai_key');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_twitter_key');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_twitter_secret');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_access_token');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_access_secret');
    // 時間帯設定の追加
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_quiet_start');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_quiet_end');
    register_setting('auto_tweet_gpt_options', 'auto_tweet_gpt_time_control_enabled');
}
add_action('admin_init', 'auto_tweet_gpt_register_settings');

// 設定セクションとフィールドの作成
function auto_tweet_gpt_settings_fields()
{
    add_settings_section(
        'auto_tweet_gpt_main_section',
        '主要設定',
        null,
        'auto-tweet-gpt-4'
    );
    // 時間帯制御セクションの追加
    add_settings_section(
        'auto_tweet_gpt_time_section',
        '投稿時間設定',
        'auto_tweet_gpt_time_section_callback',
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

    // 時間帯制御の有効/無効設定
    add_settings_field(
        'auto_tweet_gpt_time_control_enabled',
        '時間帯制御',
        'auto_tweet_gpt_time_control_enabled_field',
        'auto-tweet-gpt-4',
        'auto_tweet_gpt_time_section'
    );

    // 投稿制限開始時刻
    add_settings_field(
        'auto_tweet_gpt_quiet_start',
        '投稿制限開始時刻',
        'auto_tweet_gpt_quiet_start_field',
        'auto-tweet-gpt-4',
        'auto_tweet_gpt_time_section'
    );

    // 投稿制限終了時刻
    add_settings_field(
        'auto_tweet_gpt_quiet_end',
        '投稿制限終了時刻',
        'auto_tweet_gpt_quiet_end_field',
        'auto-tweet-gpt-4',
        'auto_tweet_gpt_time_section'
    );
}
add_action('admin_init', 'auto_tweet_gpt_settings_fields');

// 時間帯セクションの説明
function auto_tweet_gpt_time_section_callback()
{
    echo '<p>投稿を制限する時間帯を設定します。デフォルトでは深夜0時から朝7時までの間は投稿を行いません。</p>';
}

// 各フィールドのHTML
function auto_tweet_gpt_prompts_field()
{
    $value = get_option('auto_tweet_gpt_prompts', '');
    echo "<textarea name='auto_tweet_gpt_prompts' rows='5' cols='100'>" . esc_textarea($value) . "</textarea>";
}

function auto_tweet_gpt_execution_mode_field()
{
    $value = get_option('auto_tweet_gpt_execution_mode', 'random');
?>
    <select name="auto_tweet_gpt_execution_mode">
        <option value="random" <?php selected($value, 'random'); ?>>ランダム</option>
        <option value="sequential" <?php selected($value, 'sequential'); ?>>順番</option>
    </select>
<?php
}

function auto_tweet_gpt_hashtags_field()
{
    $value = get_option('auto_tweet_gpt_hashtags', '');
    echo "<input type='text' name='auto_tweet_gpt_hashtags' value='" . esc_attr($value) . "' class='regular-text' />";
}

function auto_tweet_gpt_frequency_field()
{
    $value = get_option('auto_tweet_gpt_frequency', 60);
    echo "<input type='number' name='auto_tweet_gpt_frequency' value='" . esc_attr($value) . "' class='small-text' /> 分";
}

function auto_tweet_gpt_openai_key_field()
{
    $value = get_option('auto_tweet_gpt_openai_key', '');
    echo "<input type='text' name='auto_tweet_gpt_openai_key' value='" . esc_attr($value) . "' class='regular-text' />";
}

function auto_tweet_gpt_twitter_key_field()
{
    $value = get_option('auto_tweet_gpt_twitter_key', '');
    echo "<input type='text' name='auto_tweet_gpt_twitter_key' value='" . esc_attr($value) . "' class='regular-text' />";
}

function auto_tweet_gpt_twitter_secret_field()
{
    $value = get_option('auto_tweet_gpt_twitter_secret', '');
    echo "<input type='text' name='auto_tweet_gpt_twitter_secret' value='" . esc_attr($value) . "' class='regular-text' />";
}

function auto_tweet_gpt_access_token_field()
{
    $value = get_option('auto_tweet_gpt_access_token', '');
    echo "<input type='text' name='auto_tweet_gpt_access_token' value='" . esc_attr($value) . "' class='regular-text' />";
}

function auto_tweet_gpt_access_secret_field()
{
    $value = get_option('auto_tweet_gpt_access_secret', '');
    echo "<input type='text' name='auto_tweet_gpt_access_secret' value='" . esc_attr($value) . "' class='regular-text' />";
}

// 時間帯制御の有効/無効設定フィールド
function auto_tweet_gpt_time_control_enabled_field()
{
    $enabled = get_option('auto_tweet_gpt_time_control_enabled', 1); // デフォルトで有効
?>
    <label>
        <input type="checkbox" name="auto_tweet_gpt_time_control_enabled" value="1" <?php checked(1, $enabled); ?> />
        時間帯による投稿制限を有効にする
    </label>
<?php
}

// 投稿制限開始時刻フィールド
function auto_tweet_gpt_quiet_start_field()
{
    $quiet_start = get_option('auto_tweet_gpt_quiet_start', '00:00');
?>
    <input type="time" name="auto_tweet_gpt_quiet_start" value="<?php echo esc_attr($quiet_start); ?>" />
    <p class="description">この時刻から投稿を制限します（デフォルト: 00:00）</p>
<?php
}

// 投稿制限終了時刻フィールド
function auto_tweet_gpt_quiet_end_field()
{
    $quiet_end = get_option('auto_tweet_gpt_quiet_end', '07:00');
?>
    <input type="time" name="auto_tweet_gpt_quiet_end" value="<?php echo esc_attr($quiet_end); ?>" />
    <p class="description">この時刻まで投稿を制限します（デフォルト: 07:00）</p>
<?php
}

// ツイートログを表示する関数
function auto_tweet_gpt_display_tweet_log() {
    $tweet_log = get_option('auto_tweet_gpt_tweet_log', array());
    $tweet_log = array_reverse($tweet_log); // 最新順に表示

    if (empty($tweet_log)) {
        echo '<p>No tweets logged yet.</p>';
    } else {
        echo '<ul class="tweet-log-list">';
        foreach (array_slice($tweet_log, 0, 50) as $log) {
            echo '<li class="tweet-log-item">';
            echo '<span class="tweet-log-time">' . esc_html($log['time']) . '</span>';
            echo '<span class="tweet-log-content">' . esc_html($log['content']) . '</span>';
            echo '</li>';
        }
        echo '</ul>';
    }
}

// ツイートの即時投稿処理
add_action('admin_init', function () {
    if (isset($_POST['test_tweet'])) {
        auto_tweet_gpt_execute();
        wp_redirect(admin_url('options-general.php?page=auto-tweet-gpt-4'));
        exit;
    }
});

// ツイートログを保存する関数
function auto_tweet_gpt_save_tweet_log($content)
{
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
function auto_tweet_gpt_schedule()
{
    if (wp_next_scheduled('auto_tweet_gpt_event')) {
        wp_clear_scheduled_hook('auto_tweet_gpt_event');
    }

    $frequency = (int)get_option('auto_tweet_gpt_frequency', 60);
    wp_schedule_event(time(), 'auto_tweet_gpt_custom_interval', 'auto_tweet_gpt_event');
}

add_filter('cron_schedules', function ($schedules) {
    $frequency = (int)get_option('auto_tweet_gpt_frequency', 60);
    $schedules['auto_tweet_gpt_custom_interval'] = array(
        'interval' => $frequency * 60,
        'display'  => __('Custom Interval')
    );
    return $schedules;
});

// プロンプト（問い合わせ内容）を取得する関数
function auto_tweet_gpt_get_prompt()
{
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
function auto_tweet_gpt_execute()
{
    try {
        // 時間帯制御が有効な場合のみチェック
        if (get_option('auto_tweet_gpt_time_control_enabled', 1)) {
            $timezone = new DateTimeZone('Asia/Tokyo');
            $now = new DateTime('now', $timezone);
            $current_time = $now->format('H:i');

            $quiet_start = get_option('auto_tweet_gpt_quiet_start', '00:00');
            $quiet_end = get_option('auto_tweet_gpt_quiet_end', '07:00');

            // 現在時刻が制限時間内かチェック
            if ($quiet_start <= $current_time && $current_time < $quiet_end) {
                error_log('Auto Tweet GPT: 投稿制限時間帯のため、ツイートをスキップしました。時刻: ' . $now->format('Y-m-d H:i:s'));
                return;
            }
        }

        $prompt = auto_tweet_gpt_get_prompt();
        $openai_key = get_option('auto_tweet_gpt_openai_key', '');
        $twitter_key = get_option('auto_tweet_gpt_twitter_key', '');
        $twitter_secret = get_option('auto_tweet_gpt_twitter_secret', '');
        $access_token = get_option('auto_tweet_gpt_access_token', '');
        $access_secret = get_option('auto_tweet_gpt_access_secret', '');

        // API キーのバリデーション
        if (empty($openai_key)) {
            throw new Exception('OpenAI APIキーが設定されていません。');
        }
        if (empty($twitter_key) || empty($twitter_secret) || empty($access_token) || empty($access_secret)) {
            throw new Exception('Twitter APIキーが正しく設定されていません。');
        }

        // OpenAI APIへのリクエスト
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $openai_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => json_encode(array(
                'model'    => 'gpt-4o',  // 
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)  // roleをuserに修正
                ),
                'max_tokens' => 150,  // トークン数制限を追加
            )),
        ));

        if (is_wp_error($response)) {
            throw new Exception('OpenAI APIエラー: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('OpenAI APIからの応答が不正です。');
        }

        $content = $data['choices'][0]['message']['content'];
        $hashtags = get_option('auto_tweet_gpt_hashtags', '');

        // ツイート文字数制限（URLや画像を考慮して280文字に制限）
        $max_length = 280 - mb_strlen($hashtags) - 1;
        $tweet = mb_substr($content, 0, $max_length) . ($hashtags ? ' ' . $hashtags : '');

        // TwitterOAuthの初期化
        $connection = new TwitterOAuth(
            $twitter_key,
            $twitter_secret,
            $access_token,
            $access_secret
        );
        $connection->setTimeouts(10, 15);  // 接続タイムアウトを設定

        // ツイートの投稿
        $result = $connection->post('tweets', ['text' => $tweet]);  // APIエンドポイントを修正

        $status_code = $connection->getLastHttpCode();
        if ($status_code === 201) {  // Twitter API v2では201が成功
            auto_tweet_gpt_save_tweet_log($tweet);
            error_log('Tweet successful: ' . $tweet);
        } else {
            throw new Exception('Twitter API Error: Status ' . $status_code . ', Response: ' . print_r($result, true));
        }
    } catch (Exception $e) {
        error_log('Auto Tweet GPT Error: ' . $e->getMessage());
        // 管理画面で表示するためにエラーを保存
        update_option('auto_tweet_gpt_last_error', date('Y-m-d H:i:s') . ': ' . $e->getMessage());
    }
}
add_action('auto_tweet_gpt_event', 'auto_tweet_gpt_execute');

// プラグイン有効化時のスケジュール設定
register_activation_hook(__FILE__, 'auto_tweet_gpt_schedule');

// プラグイン無効化時のスケジュール解除
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('auto_tweet_gpt_event');
});


function auto_tweet_gpt_custom_styles() {
    echo '
    <style>
        .tweet-log-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .tweet-log-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
            padding: 15px;
            margin-bottom: 10px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .tweet-log-time {
            font-size: 0.9em;
            color: #777;
        }
        .tweet-log-content {
            font-size: 1em;
            color: #333;
        }
    </style>
    ';
}
add_action('admin_head', 'auto_tweet_gpt_custom_styles');

