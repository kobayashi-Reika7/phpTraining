<?php
/**
 * 確認画面ビュー
 *
 * 入力内容を一覧表示し、「修正」「送信」のどちらかを選ばせる。
 * hidden フィールドで入力値を次のリクエストに引き継ぐ。
 */

// 確認画面に表示するフィールド定義（ラベルと表示用の値変換を一括管理）
$display_fields = array(
	'name'   => array('label' => '名前',               'value' => $input['name']),
	'email'  => array('label' => 'メールアドレス',       'value' => $input['email']),
	'comment'=> array('label' => 'コメント',             'value' => nl2br($input['comment'])),
	'gender' => array('label' => '性別',                 'value' => $input['gender']),
	'kind'   => array('label' => '問い合わせの種類',      'value' => $input['kind']),
	'lang'   => array('label' => '使用プログラミング言語', 'value' => implode(' ', $input['lang'])),
);
?>
<h2>入力内容の確認</h2>

<dl>
<?php foreach ($display_fields as $field): ?>
	<dt><?php echo $field['label']; ?></dt>
	<dd><?php echo $field['value']; ?></dd>
<?php endforeach; ?>
</dl>

<?php
// hidden フィールド生成用のヘルパー（重複コードを排除）
// dont_prep: FuelPHP のHTML エスケープ二重適用を防ぐオプション
$hidden_opts = array('dont_prep' => true);
?>

<!-- 修正ボタン: 入力画面に戻す -->
<?php echo Form::open('form/'); ?>
<?php foreach (array('name', 'email', 'comment', 'gender', 'kind') as $field_name): ?>
	<?php echo Form::hidden($field_name, $input[$field_name], $hidden_opts); ?>
<?php endforeach; ?>
<?php foreach ($input['lang'] as $lang): ?>
	<?php echo Form::hidden('lang[]', $lang, $hidden_opts); ?>
<?php endforeach; ?>
<div class="actions">
	<?php echo Form::submit('submit1', '修正'); ?>
</div>
<?php echo Form::close(); ?>

<!-- 送信ボタン: メール送信処理へ（CSRF トークン付き） -->
<?php echo Form::open('form/send'); ?>
<?php echo Form::hidden(Config::get('security.csrf_token_key'), Security::fetch_token()); ?>
<?php foreach (array('name', 'email', 'comment', 'gender', 'kind') as $field_name): ?>
	<?php echo Form::hidden($field_name, $input[$field_name], $hidden_opts); ?>
<?php endforeach; ?>
<?php foreach ($input['lang'] as $lang): ?>
	<?php echo Form::hidden('lang[]', $lang, $hidden_opts); ?>
<?php endforeach; ?>
<div class="actions">
	<?php echo Form::submit('submit2', '送信'); ?>
</div>
<?php echo Form::close(); ?>
