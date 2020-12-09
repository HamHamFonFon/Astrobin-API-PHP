<?php

declare(strict_types=1);

namespace AstrobinWs\Services;

use AstrobinWs\AbstractWebService;
use AstrobinWs\Exceptions\WsException;
use AstrobinWs\Exceptions\WsResponseException;
use AstrobinWs\Response\AstrobinResponse;
use AstrobinWs\Response\Collection;
use AstrobinWs\Response\Image;
use AstrobinWs\Response\ListImages;

/**
 * Class GetImage
 *
 * @package Astrobin\Services
 */
class GetImage extends AbstractWebService implements WsInterface
{
    public const END_POINT = 'image';

    /**
     * @return string
     */
    protected function getEndPoint(): string
    {
        return self::END_POINT;
    }

    /**
     * @return string
     */
    protected function getObjectEntity(): string
    {
        return Image::class;
    }

    /**
     * @return string
     */
    protected function getCollectionEntity(): string
    {
        return ListImages::class;
    }

    /**
     * Only for retro-compatibility with version 1.x
     *
     * @param $id
     *
     * @return AstrobinResponse|null
     * @throws WsException
     * @throws WsResponseException
     * @throws \ReflectionException
     */
    public function getImageById(string $id): ?AstrobinResponse
    {
        return $this->getById($id);
    }

    /**
     * Id of image can be a string or an int
     *
     * @param string|null $id
     *
     * @return AstrobinResponse
     * @throws WsException
     * @throws WsResponseException
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public function getById(?string $id): ?AstrobinResponse
    {
        if (is_null($id)) {
            throw new WsResponseException(sprintf("[Astrobin response] '%s' is not a correct value, alphanumeric expected", $id), 500, null);
        }
        $response = $this->get($id, null);
        return $this->buildResponse($response);
    }

    /**
     * Return a collection of Image()
     *
     * @param $subjectId
     * @param $limit
     *
     * @return ListImages|Image|null
     * @throws WsResponseException
     * @throws WsException
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public function getImagesBySubject(string $subjectId, int $limit): ?AstrobinResponse
    {
        if (parent::LIMIT_MAX < $limit) {
            return null;
        }

        $params = ['subjects' => $subjectId, 'limit' => $limit];
        $response = $this->get(null, $params);
        return $this->buildResponse($response);
    }


    /**
     * @param $description
     * @param $limit
     *
     * @return ListImages|Image|null
     * @throws WsResponseException
     * @throws WsException
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public function getImagesByDescription(string $description, int $limit): ?AstrobinResponse
    {
        if (parent::LIMIT_MAX < $limit) {
            return null;
        }

        $params = ['title__icontains' => urlencode($description), 'limit' => $limit];
        $response = $this->get(null, $params);
        return $this->buildResponse($response);
    }


    /**
     * Return an Collection per user name
     *
     * @param $userName
     * @param $limit
     *
     * @return ListImages|Image|null
     * @throws WsResponseException
     * @throws WsException
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public function getImagesByUser(string $userName, int $limit): ?AstrobinResponse
    {
        if (parent::LIMIT_MAX < $limit) {
            return null;
        }

        $params = ['user' => $userName, 'limit' => $limit];
        $response = $this->get(null, $params);

        return $this->buildResponse($response);
    }


    /**
     * @param string|null $dateFromStr
     * @param string|null $dateToStr
     *
     * @return ListImages|Image|null
     * @throws WsException
     * @throws WsResponseException
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function getImagesByRangeDate(?string $dateFromStr, ?string $dateToStr): ?AstrobinResponse
    {
        if (is_null($dateToStr)) {
            /**
             * @var \DateTimeInterface $dateTo
            */
            $dateTo = new \DateTime('now');
        } else {
            /**
             * @var \DateTimeInterface $dateTo
            */
            $dateTo = new \DateTime($dateToStr);
        }

        if (false === strtotime($dateFromStr)) {
            throw new WsException(sprintf("Format \"%s\" is not a correct format, please use YYYY-mm-dd", $dateFromStr), 500, null);
        }

        /**
         * @var \DateTime $dateFrom
        */
        $dateFrom = \DateTime::createFromFormat('Y-m-d', $dateFromStr);
        if ($dateFromStr !== $dateFrom->format('Y-m-d')) {
            throw new WsException(sprintf("Format \"%s\" is not a correct format for a date, please use YYYY-mm-dd instead", $dateFromStr), 500, null);
        }

        if (false !== $dateFrom && array_sum($dateFrom->getLastErrors())) {
            throw new WsException("Error date format : \n" . print_r($dateFrom->getLastErrors()), 500, null);
        }

        $params = [
            'uploaded__gte' => urlencode($dateFrom->format('Y-m-d 00:00:00')),
            'uploaded__lt' => urlencode($dateTo->format('Y-m-d H:i:s')),
            'limit' => AbstractWebService::LIMIT_MAX
        ];

        $response = $this->get(null, $params);
        return $this->buildResponse($response);
    }
}
