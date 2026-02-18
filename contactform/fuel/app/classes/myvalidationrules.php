<?php
/**
 * Sample Contact Form
 *
 * @author     Kenji Suzuki https://github.com/kenjis
 * @copyright  2011-2012 Kenji Suzuki
 * @license    MIT License http://www.opensource.org/licenses/mit-license.php
 */

/**
 * カスタムバリデーションルール
 *
 * FuelPHP の Validation クラスに組み込むカスタムルール。
 * メソッド名の "_validation_" プレフィックスが FuelPHP の規約。
 * add_rule('in_array', ...) → _validation_in_array() が呼ばれる。
 */
class MyValidationRules
{
	/**
	 * 選択肢が許可値リストに含まれるかチェック
	 *
	 * select, radio, checkbox の入力値が、定義済みの選択肢に含まれるか検証する。
	 * 空値は許可（任意フィールド対応）。配列（checkbox）にも対応。
	 *
	 * @param  string|array $val     ユーザーの入力値
	 * @param  array        $compare 許可される値の配列
	 * @return true 許可値に含まれる場合
	 * @throws HttpInvalidInputException 許可値に含まれない場合
	 */
	public static function _validation_in_array($val, $compare)
	{
		if (Validation::_empty($val))
		{
			return true;
		}

		if ( ! is_array($val))
		{
			$val = array($val);
		}

		foreach ($val as $value)
		{
			if ( ! in_array($value, $compare))
			{
				throw new HttpInvalidInputException('Invalid input data');
			}
		}

		return true;
	}

	/**
	 * 任意の配列入力を正規化する
	 *
	 * checkbox のように複数選択のフィールドで、
	 * 何も選択されなかった場合に空配列を返す。
	 * 配列で届いた場合はそのまま通す。
	 *
	 * @param  array|null $val ユーザーの入力値
	 * @return true|array 配列なら true、それ以外なら空配列を返す
	 */
	public static function _validation_not_required_array($val)
	{
		if (is_array($val))
		{
			return true;
		}
		else
		{
			return array();
		}
	}

	/**
	 * タブ・改行を含まないかチェック
	 *
	 * メールヘッダインジェクション対策。
	 * 名前やメールアドレスに \r \n \t が含まれると攻撃に利用される恐れがある。
	 *
	 * @param  string $value ユーザーの入力値
	 * @return bool タブ・改行が含まれなければ true
	 */
	public static function _validation_no_tab_and_newline($value)
	{
		return (preg_match('/\A[^\r\n\t]*\z/u', $value) === 1);
	}
}
