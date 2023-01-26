<?php
namespace MCS;

use DateTime;
use SimpleXMLElement;
use DateTimeZone;
use Exception;
use Psr\Http\Message\ResponseInterface;

class MWSResponse
{
	const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.\\0\\0\\0\\Z';
	const X_MWS_QUOTA_MAX = 'x-mws-quota-max';
	const X_MWS_QUOTA_REMAINING = 'x-mws-quota-remaining';
	const X_MWS_QUOTA_RESETS_ON = 'x-mws-quota-resetsOn';

	/** @var ResponseInterface|null */
	private $response;

	/** @var mixed */
	private $updatedResponse;

	/** @var int */
	private $quotaMax;

	/** @var int */
	private $quotaRemaining;

	/** @var DateTime */
	private $quotaResetsOn;

	/**
	 * MWSResponse constructor.
	 * @param ResponseInterface $response
	 */
	public function __construct(ResponseInterface $response = null)
	{
		$this->response = $response;
		$this->parseHeaders();
	}

	/**
	 * Get modified response
	 * When is empty, returns original request response
	 *
	 * @return mixed
	 */
	public function getResponse()
	{
		if ($this->updatedResponse === null) {
			return $this->getResponse();
		}

		return $this->updatedResponse;
	}

	/**
	 * Set new modified and clear response
	 *
	 * @param mixed $updatedResponse
	 *
	 * @return self
	 */
	public function setUpdatedResponse($updatedResponse)
	{
		$this->updatedResponse = $updatedResponse;
		return $this;
	}

	/**
	 * Get original request response
	 *
	 * @param bool $raw
	 * @return mixed|string|null
	 */
	public function getOriginalResponse($raw = false) {
		if ($this->response === null) {
			return null;
		}

		$body = (string) $this->response->getBody();

		if ($raw) {
			return $body;
		} else if (strpos(strtolower($this->response->getHeader('Content-Type')[0]), 'xml') !== false) {
			return json_decode(json_encode(simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NOCDATA)), true);
		} else {
			return $body;
		}
	}

	/**
	 * @return int
	 */
	public function getQuotaMax()
	{
		return $this->quotaMax;
	}

	/**
	 * @return int
	 */
	public function getQuotaRemaining()
	{
		return $this->quotaRemaining;
	}

	/**
	 * Date is int GMT for convert to your time zone use parameter
	 *
	 * @param DateTimeZone|null $dateTimeZone
	 * @return DateTime
	 *
	 * @throws Exception
	 */
	public function getQuotaResetsOn(DateTimeZone $dateTimeZone = null)
	{
		if ($dateTimeZone !== null && $this->quotaResetsOn !== null) {
			$date = new DateTime('now', $dateTimeZone);
			$date->setTimestamp($this->quotaResetsOn->getTimestamp());

			return $date;
		}

		return $this->quotaResetsOn;
	}

	/**
	 * Parse headers for quota estimate
	 */
	private function parseHeaders()
	{
		$maxQuotaHeader = $this->response->getHeader(self::X_MWS_QUOTA_MAX);
		$quotaRemainingHeader = $this->response->getHeader(self::X_MWS_QUOTA_REMAINING);
		$this->quotaMax = count($maxQuotaHeader) > 0 ? (int) $maxQuotaHeader[0] : null;
		$this->quotaRemaining = count($quotaRemainingHeader) > 0 ? (int) $quotaRemainingHeader[0] : null;

		$resetsOnHeader = $this->response->getHeader(self::X_MWS_QUOTA_RESETS_ON);
		if ($resetsOnHeader && count($resetsOnHeader) > 0) {
			$this->quotaResetsOn = DateTime::createFromFormat(
				self::DATE_TIME_FORMAT,
				$resetsOnHeader[0],
				new DateTimeZone('UTC')
			);
		}
	}
}
