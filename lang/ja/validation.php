<?php

// lang/ja/validation.php

return [
    'required' => ':attributeは必須です。',
    'string' => ':attributeは文字列で入力してください。',
    'integer' => ':attributeは整数で入力してください。',
    'array' => ':attributeは配列で入力してください。',
    'date' => ':attributeは正しい日付形式で入力してください。',
    'email' => ':attributeはメールアドレス形式で入力してください。',
    'confirmed' => ':attributeが確認用と一致しません。',
    'unique' => 'その:attributeは既に使用されています。',
    'exists' => '選択された:attributeは存在しません。',
    'in' => '選択された:attributeは正しくありません。',
    'digits' => ':attributeは:digits桁の数字で入力してください。',
    'url' => ':attributeは正しいURL形式で入力してください。',
    'max' => [
        'string' => ':attributeは:max文字以内で入力してください。',
        'numeric' => ':attributeは:max以下で指定してください。',
    ],
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください。',
        'numeric' => ':attributeは:min以上で指定してください。',
        'array' => ':attributeは:min個以上選択してください。',
    ],

    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'title' => 'タイトル',
        'author' => '著者名',
        'isbn' => 'ISBN',
        'published_date' => '出版日',
        'description' => '説明',
        'image_url' => '画像URL',
        'genres' => 'ジャンル',
        'rating' => '評価',
        'comment' => 'コメント',
        'per_page' => '1ページあたりの件数',
        'page' => 'ページ番号',
        'user_id' => '登録者',
        'keyword' => 'キーワード',
    ],
];
