<?php
/**
 * Sample Contact Form
 *
 * @author     Kenji Suzuki https://github.com/kenjis
 * @copyright  2011 Kenji Suzuki
 * @license    MIT License http://www.opensource.org/licenses/mit-license.php
 */

/**
 * フォームコントローラ
 *
 * コンタクトフォームの表示・確認・送信を担当する。
 * FuelPHP の Controller_Template を継承し、テンプレートエンジンを利用。
 *
 * 画面フロー: index（入力）→ confirm（確認）→ send（送信）
 */
class Controller_Form extends Controller_Template
{
	/**
	 * リクエスト前処理
	 * セキュリティヘッダ（クリックジャッキング対策）を設定
	 */
	public function before()
	{
		parent::before();
		$this->response = Response::forge();
		$this->response->set_header('X-FRAME-OPTIONS', 'SAMEORIGIN');
	}

	/**
	 * リクエスト後処理
	 * テンプレートをレスポンスボディに設定
	 */
	public function after($response)
	{
		$response = $this->response;
		$response->body = $this->template;
		return parent::after($response);
	}

	/**
	 * フォームフィールド定義を構築する
	 *
	 * Fieldset を使ってフォームの入力項目・バリデーションルールを一括定義。
	 * 各フィールドに add_rule() でサーバー側バリデーションを設定している。
	 *
	 * @return Fieldset 構築済みのフォーム定義
	 */
	public function form()
	{
		$form = Fieldset::forge();

		// 名前: 必須・タブ改行禁止・最大20文字
		$form->add('name', '名前')
			->add_rule('trim')
			->add_rule('required')
			->add_rule('no_tab_and_newline')
			->add_rule('max_length', 20);

		// メールアドレス: 必須・タブ改行禁止・メール形式
		$form->add('email', 'メールアドレス')
			->add_rule('trim')
			->add_rule('required')
			->add_rule('no_tab_and_newline')
			->add_rule('valid_email');

		// コメント: 必須・最大400文字
		$form->add('comment', 'コメント',
			array('type' => 'textarea', 'cols' => 70, 'rows' => 6))
			->add_rule('required')
			->add_rule('max_length', 400);

		// 性別: ラジオボタン（任意）
		$gender_options = array(
			'男性' => '男性',
			'女性' => '女性',
		);
		$form->add('gender', '性別',
			array('options' => $gender_options, 'type' => 'radio'))
			->add_rule('in_array', $gender_options);

		// 問い合わせの種類: セレクトボックス（任意）
		$kind_options = array(
			''                         => '',
			'製品購入前のお問い合わせ' => '製品購入前のお問い合わせ',
			'製品購入後のお問い合わせ' => '製品購入後のお問い合わせ',
			'その他'                   => 'その他',
		);
		$form->add('kind', '問い合わせの種類',
			array('options' => $kind_options, 'type' => 'select'))
			->add_rule('in_array', $kind_options);

		// 使用プログラミング言語: チェックボックス（任意・複数選択）
		$lang_options = array(
			'PHP'    => 'PHP',
			'Perl'   => 'Perl',
			'Python' => 'Python',
		);
		$form->add('lang', '使用プログラミング言語',
			array('options' => $lang_options, 'type' => 'checkbox'))
			->add_rule('in_array', $lang_options)
			->add_rule('not_required_array');

		$form->add('submit', '', array('type'=>'submit', 'value' => '確認'));

		return $form;
	}

	/**
	 * 入力画面の表示
	 *
	 * POST リクエスト時（確認画面から「修正」で戻った場合）は
	 * repopulate() で前回の入力値をフォームに復元する。
	 */
	public function action_index()
	{
		$form = $this->form();

		if (Input::method() === 'POST')
		{
			$form->repopulate();
		}

		$this->template->title = 'コンタクトフォーム';
		$this->template->content = View::forge('form/index');
		$this->template->content->set_safe('html_form', $form->build('form/confirm'));
	}

	/**
	 * 確認画面の表示
	 *
	 * バリデーション成功時: 入力内容を確認画面に表示
	 * バリデーション失敗時: エラーメッセージと共に入力画面に戻す
	 */
	public function action_confirm()
	{
		$form = $this->form();
		$val  = $form->validation();
		$val->add_callable('myvalidationrules');

		if ($val->run())
		{
			$data['input'] = $val->validated();
			$this->template->title = 'コンタクトフォーム: 確認';
			$this->template->content = View::forge('form/confirm', $data);
		}
		else
		{
			$form->repopulate();

			$this->template->title = 'コンタクトフォーム: エラー';
			$this->template->content = View::forge('form/index');
			$this->template->content->set_safe('html_error', $val->show_errors());
			$this->template->content->set_safe('html_form', $form->build('form/confirm'));
		}
	}

	/**
	 * メール送信処理
	 *
	 * 処理フロー:
	 * 1. CSRF トークンを検証（不正リクエスト防止）
	 * 2. バリデーション再実行（改ざん防止）
	 * 3. メールデータを組み立て
	 * 4. sendmail() でメール送信
	 *
	 * 例外発生時はエラー画面を表示し、ログに記録する。
	 */
	public function action_send()
	{
		// CSRF トークン検証（不正な POST リクエストを拒否）
		if ( ! \Security::check_token())
		{
			\Log::error(
				'CSRF: '.
				\Input::uri().' '.
				\Input::ip().
				' "'.\Input::user_agent().'"'
			);
			throw new HttpInvalidInputException('Invalid input data');
		}

		$val = $this->form()->validation();
		$val->add_callable('myvalidationrules');

		if ($val->run())
		{
			$post = $val->validated();
			$email_data = $this->build_email_data($post);

			try
			{
				$this->sendmail($email_data);
				$this->template->title = 'コンタクトフォーム: 送信完了';
				$this->template->content = View::forge('form/send');
			}
			catch(EmailValidationFailedException $e)
			{
				$this->template->title = 'コンタクトフォーム: 送信エラー';
				$this->template->content = View::forge('form/error');
				\Log::error(__METHOD__ . ' email validation error: ' . $e->getMessage());
			}
			catch(EmailSendingFailedException $e)
			{
				$this->template->title = 'コンタクトフォーム: 送信エラー';
				$this->template->content = View::forge('form/error');
				\Log::error(__METHOD__ . ' email sending error: ' . $e->getMessage());
			}
		}
		else
		{
			$this->template->title = 'コンタクトフォーム: エラー';
			$this->template->content = View::forge('form/index');
			$this->template->content->set_safe('html_error', $val->show_errors());
		}
	}

	/**
	 * メール送信データを組み立てる
	 *
	 * バリデーション済みの入力値と設定ファイルの情報を組み合わせて、
	 * sendmail() に渡すデータ配列を作成する。
	 *
	 * @param array $post バリデーション済みの入力データ
	 * @return array メール送信に必要なデータ
	 */
	private function build_email_data(array $post)
	{
		\Config::load('contact_form', true);

		$ip    = \Input::ip();
		$agent = \Input::user_agent();
		$langs = implode(' ', $post['lang']);

		return array(
			'from'      => $post['email'],
			'from_name' => $post['name'],
			'to'        => \Config::get('contact_form.admin_email'),
			'to_name'   => \Config::get('contact_form.admin_name'),
			'subject'   => \Config::get('contact_form.mail_subject'),
			'body'      => <<< END
====================
名前: {$post['name']}
メールアドレス: {$post['email']}
IPアドレス: $ip
ブラウザ: $agent
====================
コメント:
{$post['comment']}

性別: {$post['gender']}
問い合わせの種類: {$post['kind']}
使用プログラミング言語: $langs
====================
END
		);
	}

	/**
	 * メールを送信する
	 *
	 * 送信前にヘッダインジェクション攻撃を検出するため、
	 * from/to/subject 等に改行コードが含まれていないかチェックする。
	 *
	 * @param array $data メール送信データ（from, to, subject, body 等）
	 * @throws EmailValidationFailedException ヘッダに改行が含まれる場合
	 * @throws EmailSendingFailedException    メール送信に失敗した場合
	 */
	public function sendmail($data)
	{
		Package::load('email');

		// メールヘッダインジェクション対策: ヘッダ値に改行がないか検証
		$header_fields = array('from', 'from_name', 'to', 'to_name', 'subject');
		foreach ($header_fields as $field)
		{
			if (preg_match('/[\r\n]/u', $data[$field]) === 1)
			{
				throw new EmailValidationFailedException(
					'One or more email headers did not pass validation: ' . $field
				);
			}
		}

		$email = Email::forge();
		$email->from($data['from'], $data['from_name']);
		$email->to($data['to'], $data['to_name']);
		$email->subject($data['subject']);
		$email->body($data['body']);
		$email->send();
	}
}
