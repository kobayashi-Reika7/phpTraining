<?php
/**
 * 入力画面ビュー
 *
 * フォームの入力フィールドを表示する。
 * バリデーションエラーがあれば $html_error に表示される。
 * $html_form は Controller_Form::form() で構築した Fieldset の HTML。
 */
?>
<h2>お問い合わせ入力</h2>

<?php if (isset($html_error)): ?>
<div class="error-messages">
	<?php echo $html_error; ?>
</div>
<?php endif; ?>

<?php echo $html_form; ?>
