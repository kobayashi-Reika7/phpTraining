<?php
/**
 * Sample Contact Form
 *
 * @author     Kenji Suzuki https://github.com/kenjis
 * @copyright  2011-2012 Kenji Suzuki
 * @license    MIT License http://www.opensource.org/licenses/mit-license.php
 */

/**
 * カスタム入力フィルタ
 *
 * FuelPHP の Input クラスが受け取る値を事前にフィルタリングする。
 * バリデーションの「前」に実行され、不正な入力を早期に排除する。
 *
 * - check_encoding: 文字エンコーディングが正しいか
 * - check_control: 不正な制御文字が含まれていないか
 * - standardize_newline: 改行コードを \n に統一
 */
class MyInputFilters
{
	/**
	 * 文字エンコーディングを検証する
	 *
	 * UTF-8 以外の不正なバイト列が含まれていないか確認。
	 * 文字化け攻撃や XSS の前段階を防ぐ。
	 *
	 * @param  string|array $value 入力値（配列の場合は再帰処理）
	 * @return string|array 検証済みの値
	 * @throws HttpInvalidInputException 不正なエンコーディングの場合
	 */
	public static function check_encoding($value)
	{
		if (is_array($value))
		{
			array_map(['MyInputFilters', 'check_encoding'], $value);
			return $value;
		}

		if (mb_check_encoding($value, Fuel::$encoding))
		{
			return $value;
		}

		static::log_error('Invalid character encoding', $value);
		throw new HttpInvalidInputException('Invalid input data');
	}

	/**
	 * 不正な制御文字を検出する
	 *
	 * タブ(\t)、改行(\r\n)以外の制御文字（NULL バイト等）を拒否。
	 * これらの制御文字はセキュリティ上の脅威になりうる。
	 *
	 * @param  string|array $value 入力値（配列の場合は再帰処理）
	 * @return string|array 検証済みの値
	 * @throws HttpInvalidInputException 不正な制御文字を含む場合
	 */
	public static function check_control($value)
	{
		if (is_array($value))
		{
			array_map(['MyInputFilters', 'check_control'], $value);
			return $value;
		}

		if (preg_match('/\A[\r\n\t[:^cntrl:]]*\z/u', $value) === 1)
		{
			return $value;
		}

		static::log_error('Invalid control characters', $value);
		throw new HttpInvalidInputException('Invalid input data');
	}

	/**
	 * 改行コードを \n に統一する
	 *
	 * Windows(\r\n) や旧Mac(\r) の改行を Unix(\n) に変換。
	 * バリデーションやメール送信時に改行の扱いを統一するため。
	 *
	 * @param  string|array $value 入力値（配列の場合は再帰処理）
	 * @return string 改行統一済みの値
	 */
	public static function standardize_newline($value)
	{
		if (is_array($value))
		{
			array_map(['MyInputFilters', 'standardize_newline'], $value);
			return $value;
		}

		if (strpos($value, "\r") !== false)
		{
			$value = str_replace(["\r\n", "\r"], "\n", $value);
		}

		return $value;
	}

	/**
	 * 不正入力のエラーログを記録する
	 *
	 * @param string $msg   エラーメッセージ
	 * @param string $value 不正な入力値（URL エンコードして記録）
	 */
	public static function log_error($msg, $value)
	{
		Log::error(
			$msg . ': ' . Input::uri() . ' ' . urlencode($value) . ' ' .
			Input::ip() . ' "' . Input::user_agent() . '"'
		);
	}
}
