<?php

namespace Console\App\Helpers;

use GuzzleHttp\ClientInterface;

class HttpHelper
{
	/**
	 * @var ClientInterface|null
	 */
	protected $client = null;

	/**
	 * @var array
	 */
	protected $headers = [];

	/**
	 * HttpHelper constructor.
	 * @param ClientInterface $client
	 */
	public function __construct(ClientInterface $client)
	{
		$this->client = $client;
		$this->headers = $this->getDefaultHeaders();
	}

	/**
	 * @param string $headerKey
	 * @param string $headerValue
	 */
	public function setHeader(string $headerKey, string $headerValue)
	{
		$this->headers[$headerKey] = $headerValue;
	}

	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * @param string $headerKey
	 * @return string
	 */
	public function getHeader(string $headerKey): string
	{
		return $this->isHeaderExist($headerKey) ? $this->headers[$headerKey] : '';
	}

	/**
	 * @param string $headerKey
	 * @return bool
	 */
	public function isHeaderExist(string $headerKey): bool
	{
		return !empty($this->headers[$headerKey]);
	}

	/**
	 * @return array
	 */
	public function getDefaultHeaders(): array
	{
		return [
			'Content-type' => 'application/vnd.api+json',
			'Accept'       => 'application/vnd.api+json',
		];
	}

	/**
	 * @return ClientInterface
	 */
	public function getClient(): ClientInterface
	{
		return $this->client;
	}

	/**
	 * @param array $options
	 * @param array $queryOptions
	 * @return string
	 */
	public function optionsToQuery(array $options, array $queryOptions): string
	{
		$query = [];
		foreach ($options as $optionKey => $option) {
			if (array_key_exists($optionKey, $queryOptions) && !empty($option)) {
				$query[$queryOptions[$optionKey]] = $option;
			} elseif (in_array($optionKey, $queryOptions) && !empty($option)) {
				$query[$optionKey] = $option;
			}
		}
		return !empty($query) ? '?' . http_build_query($query) : '';
	}
}