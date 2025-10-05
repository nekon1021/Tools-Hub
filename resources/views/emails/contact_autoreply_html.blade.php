<!DOCTYPE html>
<html lang="ja"><head><meta charset="utf-8"><title>Auto Reply</title></head>
<body>
  <p>{{ $data['name'] }} 様</p>
  <p>お問い合わせありがとうございます。以下の内容で受け付けました。</p>
  <ul>
    <li>お名前: {{ $data['name'] }}</li>
    <li>メール: {{ $data['email'] }}</li>
    <li>件名: {{ $data['subject'] ?? '件名なし' }}</li>
  </ul>
  <pre style="white-space:pre-wrap;border:1px solid #ddd;padding:8px;">{{ $data['message'] }}</pre>
</body></html>
