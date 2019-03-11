<?php
/**
 * @copyright Copyright (c) 2019 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Icewind\Streams;


class WrapperHandler {
	const NO_SOURCE_DIR = 1;

	/**
	 * get the protocol name that is generated for the class
	 * @param string|null $class
	 * @return string
	 */
	public static function getProtocol($class = null) {
		if ($class === null) {
			$class = static::class;
		}

		$parts = explode('\\', $class);
		return strtolower(array_pop($parts));
	}

	/**
	 * @param resource|int $source
	 * @param resource|array $context
	 * @param null $protocol deprecated, protocol is now automatically generated
	 * @param null $class deprecated, class is now automatically generated
	 * @return bool|resource
	 */
	protected static function wrapSource($source, $context = [], $protocol = null, $class = null) {
		if ($class === null) {
			$class = static::class;
		}

		$protocol = self::getProtocol($class);

		if (is_array($context)) {
			$context['source'] = $source;
			$context = stream_context_create([$protocol => $context]);
		}

		if ($source !== self::NO_SOURCE_DIR && !is_resource($source)) {
			throw new \BadMethodCallException();
		}
		try {
			stream_wrapper_register($protocol, $class);
			if (self::isDirectoryHandle($source)) {
				$wrapped = opendir($protocol . '://', $context);
			} else {
				$wrapped = fopen($protocol . '://', 'r+', false, $context);
			}
		} catch (\BadMethodCallException $e) {
			stream_wrapper_unregister($protocol);
			throw $e;
		}
		stream_wrapper_unregister($protocol);
		return $wrapped;
	}

	protected static function isDirectoryHandle($resource) {
		if ($resource === self::NO_SOURCE_DIR) {
			return true;
		}
		$meta = stream_get_meta_data($resource);
		return $meta['stream_type'] === 'dir' || $meta['stream_type'] === 'user-space-dir';
	}

	/**
	 * Load the source from the stream context and return the context options
	 *
	 * @param string|null $name if not set, the generated protocol name is used
	 * @return array
	 * @throws \BadMethodCallException
	 */
	protected function loadContext($name = null) {
		if ($name === null) {
			$parts = explode('\\', static::class);
			$name = strtolower(array_pop($parts));
		}

		$context = stream_context_get_options($this->context);
		if (isset($context[$name])) {
			$context = $context[$name];
		} else {
			throw new \BadMethodCallException('Invalid context, "' . $name . '" options not set');
		}
		return $context;
	}
}