@php
  // メールクライアント差異対策：行間・フォントをCSSで指定
  $subject = $data['subject'] ?? '件名なし';
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>お問い合わせありがとうございます</title>
  <style>
    /* できるだけシンプルに（Gmail/Outlook互換） */
    body { margin:0; padding:0; background:#f6f7f9; }
    .wrapper { width:100%; background:#f6f7f9; padding:24px 0; }
    .container {
      width:100%; max-width:640px; margin:0 auto; background:#ffffff;
      border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.05);
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Noto Sans JP", "Hiragino Kaku Gothic ProN", Meiryo, sans-serif;
      color:#1f2937; line-height:1.7; font-size:16px;
    }
    .header { background:#0ea5e9; color:#fff; padding:16px 24px; }
    .brand { margin:0; font-size:18px; font-weight:700; }
    .content { padding:24px; }
    .lead { margin:0 0 16px; font-size:18px; font-weight:700; color:#111827; }
    .muted { color:#6b7280; font-size:14px; }
    .table {
      width:100%; border-collapse:collapse; margin:16px 0 8px;
    }
    .table th, .table td {
      text-align:left; padding:10px 12px; vertical-align:top; border-bottom:1px solid #e5e7eb;
      word-break: break-word;
    }
    .table th { width:120px; color:#374151; background:#fafafa; }
    .message {
      white-space:pre-wrap; /* 改行を保持 */
      background:#fafafa; border:1px solid #e5e7eb; padding:12px; border-radius:6px; margin-top:8px;
    }
    .footer { padding:16px 24px 24px; color:#6b7280; font-size:12px; }
    .footer a { color:#6b7280; text-decoration:underline; }
    @media (max-width: 480px) {
      .content { padding:16px; }
      .header { padding:12px 16px; }
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="container">
      <div class="header">
        <p class="brand">Tools Hub</p>
      </div>
      <div class="content">
        <p class="lead">{{ $data['name'] }} 様</p>
        <p>この度は <strong>Tools Hub</strong> にお問い合わせいただきありがとうございます。以下の内容で受け付けました。担当より順次ご連絡いたします。</p>

        <table class="table" role="presentation">
          <tr>
            <th>日時</th>
            <td>{{ now()->format('Y-m-d H:i') }}</td>
          </tr>
          <tr>
            <th>お名前</th>
            <td>{{ $data['name'] }}</td>
          </tr>
          <tr>
            <th>メール</th>
            <td>{{ $data['email'] }}</td>
          </tr>
          <tr>
            <th>件名</th>
            <td>{{ $subject }}</td>
          </tr>
        </table>

        <div>
          <div class="muted">内容</div>
          <div class="message">{{ $data['message'] }}</div>
        </div>

        <p class="muted" style="margin-top:16px;">
          ※本メールは送信専用です。ご返信には対応できません。<br>
          ※心当たりのない場合は、このメールを破棄してください。
        </p>
      </div>
      <div class="footer">
        <div>発行：Tools Hub</div>
        <div>URL：<a href="{{ config('app.url') }}">{{ config('app.url') }}</a></div>
        <div>&copy; {{ date('Y') }} Tools Hub</div>
      </div>
    </div>
  </div>
</body>
</html>
