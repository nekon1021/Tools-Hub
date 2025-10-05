[管理者通知] Tools Hub お問い合わせ

日時: {{ now()->toDateTimeString() }}

お名前: {{ $data['name'] }}
メール: {{ $data['email'] }}
件名: {{ $data['subject'] ?? '件名なし' }}

内容:
{{ $data['message'] }}
