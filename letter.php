<?php
class letter {

	const TYPE_PLAIN = 'text/plain';
	const TYPE_HTML = 'text/html';
	const TYPE_OCTET_STREAM = 'application/octet-stream';
	
	protected $encoding = 'UTF-8';
	protected $headers = array();
	protected $to = array();
	protected $subject = null;
	protected $body = array('body' => '');

	/**
	 * @return letter
	 */
	public static function create() {
		return new self;
	}
	
	protected function encodeString($string) {
		return '=?'.$this->encoding.'?B?'.base64_encode($string).'?=';
	}
	
	protected function makeEmail($emailArray) {
		return 
			!empty($emailArray['name']) 
				? "{$this->encodeString($emailArray['name'])} <{$emailArray['email']}>"
				: $emailArray['email'];
	}
	
	protected function makeBoundary() {
		return md5(microtime(true));
	}
	
	protected function parseHeader(&$value, $key) {
		$value = "{$key}: {$value}";
	}
	
	protected function makeHeaders($headers) {
		array_walk(
			$headers,
			array($this, 'parseHeader')
		);
		
		return implode("\r\n", $headers);
	}

	protected function makeSubject($subject) {
		return $this->encodeString($subject);
	}
	
	protected function parseBodyPart(&$value, $key, $boundary) {
		$headers = array(
			'Content-Transfer-Encoding' => 'base64',
		);
		
		if (
			!empty($value['name']) 
			|| !empty($value['path'])
		) {
			if (!empty($value['path'])) {
				$value['name'] = $value['name'] ?: pathinfo($value['path'], PATHINFO_BASENAME);
				$value['data'] = file_get_contents($value['path']);
			}
			
			$headers['Content-Type'] = "{$value['type']}; name=\"{$value['name']}\"";
			$headers['Content-Disposition'] = "attachment; filename=\"{$value['name']}\"";
		}
		else {
			$headers['Content-Type'] = "{$value['type']}; charset={$this->encoding}";
		}
		
		$value = 
			"--{$boundary}\r\n"
			.$this->makeHeaders($headers)."\r\n\r\n"
			.chunk_split(base64_encode($value['data']));
	} 
	
	protected function makeBody($body, $boundary) {
		if (!$body) return;
		
		array_walk(
			$body,
			array($this, 'parseBodyPart'),
			$boundary
		);
		
		return implode("\r\n", $body)."\r\n--{$boundary}--";
	}
	
	protected function addBodyPart($name, $type, $path, $data, $partName = null) {
		$partName = $partName ?: count($this->body);
		$this->body[$partName] = compact('name', 'type', 'path', 'data');
		return $this;
	}
	
	/**
	 * Change mail encoding (in headers only).
	 * @param string $encoding
	 * @return letter
	 */
	public function encoding($encoding) {
		$this->encoding = $encoding;
		return $this;
	}
	
	/**
	 * Set header value.
	 * @param string $key
	 * @param string $value
	 * @return letter
	 */
	public function header($key, $value) {
		$this->headers[$key] = $value;
		return $this;
	}

	/**
	 * @param string $email
	 * @param string $name[optional]
	 * @return letter
	 */
	public function from($email, $name = null) {
		$this->header('From', $this->makeEmail(compact('email', 'name')));
			
		return $this;
	}

	/**
	 * @param string $email
	 * @param string $name[optional]
	 * @return letter
	 */
	public function to($email, $name = null) {
		$this->to[] = $this->makeEmail(compact('email', 'name'));
		return $this;
	}

	/**
	 * @param string $subject
	 * @return letter
	 */
	public function subject($subject) {
		$this->subject = $subject;
		return $this;
	}

	/**
	 * Set plain text body.
	 * @param string $text
	 * @return letter
	 */
	public function text($text) {
		$this->addBodyPart(null, self::TYPE_PLAIN, null, $text, 'body');
		return $this;
	}

	/**
	 * Set html body.
	 * @param string $html
	 * @return letter
	 */
	public function html($html) {
		$this->addBodyPart(null, self::TYPE_HTML, null, $html, 'body');
		return $this;
	}
	
	/**
	 * Attach data.
	 * @param string $data
	 * @param string $name
	 * @return letter
	 */
	public function data($data, $name) {
		$this->addBodyPart($name, self::TYPE_OCTET_STREAM, null, $data);
		return $this;
	}
	
	/**
	 * Attach file.
	 * @param string $path
	 * @param string[optional] $name
	 * @return letter
	 */
	public function file($path, $name = null) {
		$this->addBodyPart($name, self::TYPE_OCTET_STREAM, $path, null);
		return $this;
	}

	/**
	 * @return letter
	 */
	public function send() {
		$boundary = $this->makeBoundary();
		$this->header('Content-Type', "multipart/mixed; boundary={$boundary}");
		
		list($subject, $body, $headers) = array(
			$this->makeSubject($this->subject),
			$this->makeBody($this->body, $boundary),
			$this->makeHeaders($this->headers),
		);
		
		foreach ($this->to as $recipient) {
			mail($recipient, $subject, $body, $headers);
		}
		
		return $this;
	}

}