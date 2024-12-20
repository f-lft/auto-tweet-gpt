# Auto Tweet GPT-4 Bot for WordPress

Auto Tweet GPT-4 Botは、OpenAIのGPT-4モデルとX（旧Twitter）APIを使用して、自動でツイートを投稿するWordPressプラグインです。ツイート内容はGPT-4によって生成され、指定の間隔で自動的に投稿されます。また、投稿制限時間の設定も可能です。

## 特徴
- **GPT-4によるツイート生成**: OpenAI APIを使用して、指定したプロンプトに基づくツイート内容を生成します。
- **ツイートの自動投稿**: 指定した間隔で自動的にツイートを投稿。
- **時間帯制御機能**: 深夜や指定時間帯の投稿を制限する設定が可能です。
- **ツイートログ表示**: プラグイン管理画面でツイート履歴を閲覧可能。
- **テスト投稿機能**: テスト用のツイートを投稿して確認できます。

## 要件
- WordPress 5.0以上
- OpenAI APIキー
- X（旧Twitter）APIキー

## インストール
1. GitHubからプラグインファイルをダウンロードし、WordPressの`/wp-content/plugins/`ディレクトリに配置します。
2. WordPressの管理画面にアクセスし、「プラグイン」ページでAuto Tweet GPT-4 Botを有効化します。

## 設定方法
1. WordPress管理画面から「設定」 -> 「Auto Tweet GPT-4」にアクセスします。
2. 必要なAPIキーやプロンプトを入力し、設定を保存します。

### X（旧Twitter）APIの設定
プラグインを使用するには、X（旧Twitter）APIのアクセスキーが必要です。以下の手順でAPIキーを取得し、設定画面に入力してください。

1. [X Developer Platform](https://developer.twitter.com/)にアクセスし、アカウントを作成またはログインします。
2. 開発者用ポータルで「プロジェクトとアプリ」を選択し、新しいアプリを作成します。
3. アプリ設定でAPIキーとアクセストークンを取得し、WordPressのAuto Tweet GPT-4 Bot設定ページに入力します。

### 主な設定項目
- **プロンプトの入力**: 各行に1つずつの問い合わせ内容を設定します。
- **実行頻度**: ツイートを投稿する頻度を分単位で指定します。
- **時間帯制御**: 投稿を制限する時間帯を設定します。
- **テスト投稿**: 管理画面からテスト用のツイートを投稿できます。

## ライセンス
このプラグインは修正や改変が禁止されています。詳細はLICENSEファイルをご確認ください。

---

## バグ報告や機能要望
GitHubのIssuesページからバグ報告や機能要望を提出できます。

---

## 注意事項
- OpenAIやX APIの利用には料金が発生する場合があります。ご利用の際は各APIの使用料金や制限に注意してください。
- 投稿される内容について、倫理面や法令を遵守してください。

---

### 作者
**Futoshi Okazaki**
