<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams;

/**
 * Wrapper that provides callbacks for write, read and close
 *
 * The following options should be passed in the context when opening the stream
 * [
 *     'callback' => [
 *        'source' => resource
 *        'read'   => function($count){} (optional)
 *        'write'  => function($data){} (optional)
 *        'close'  => function(){} (optional)
 *     ]
 * ]
 *
 * All callbacks are called before the operation is executed on the source stream
 */
class CallBackWrapper extends Wrapper {
	/**
	 * @var callable
	 */
	protected $readCallback;

	/**
	 * @var callable
	 */
	protected $writeCallback;

	/**
	 * @var callable
	 */
	protected $closeCallback;

	public function stream_open() {
		$context = $this->loadContext('callback');

		if (isset($context['read']) and is_callable($context['read'])) {
			$this->readCallback = $context['read'];
		}
		if (isset($context['write']) and is_callable($context['write'])) {
			$this->writeCallback = $context['write'];
		}
		if (isset($context['close']) and is_callable($context['close'])) {
			$this->closeCallback = $context['close'];
		}
		return true;
	}

	public function stream_read($count) {
		if ($this->readCallback) {
			call_user_func($this->readCallback, $count);
		}
		return parent::stream_read($count);
	}

	public function stream_write($data) {
		if ($this->writeCallback) {
			call_user_func($this->writeCallback, $data);
		}
		return parent::stream_write($data);
	}

	public function stream_close() {
		if ($this->closeCallback) {
			call_user_func($this->closeCallback);
		}
		return parent::stream_close();
	}
}