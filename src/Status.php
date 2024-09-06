<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

/**
 * Represents a response by an application to a request. While this is primarily intended for responding to
 * HTTP requests, it is written in such a way that it could also be used in other kinds of
 * applications, such as console applications.
 */
enum Status
{
    case CONTINUE;
    case SWITCHING_PROTOCOLS;
    case PROCESSING;
    case OK;
    case CREATED;
    case ACCEPTED;
    case NON_AUTHORITATIVE_INFORMATION;
    case NO_CONTENT;
    case RESET_CONTENT;
    case PARTIAL_CONTENT;
    case MULTI_STATUS;
    case ALREADY_REPORTED;
    case MULTIPLE_CHOICES;
    case MOVED_PERMANENTLY;
    case FOUND;
    case SEE_OTHER;
    case NOT_MODIFIED;
    case USE_PROXY;
    case SWITCH_PROXY;
    case TEMPORARY_REDIRECT;
    case BAD_REQUEST;
    case UNAUTHORIZED;
    case PAYMENT_REQUIRED;
    case FORBIDDEN;
    case NOT_FOUND;
    case METHOD_NOT_ALLOWED;
    case NOT_ACCEPTABLE;
    case PROXY_AUTHENTICATION_REQUIRED;
    case REQUEST_TIMEOUT;
    case CONFLICT;
    case GONE;
    case LENGTH_REQUIRED;
    case PRECONDITION_FAILED;
    case REQUEST_ENTITY_TOO_LARGE;
    case URI_TOO_LONG;
    case UNSUPPORTED_MEDIA_TYPE;
    case RANGE_NOT_SATISFIABLE;
    case EXPECTATION_FAILED;
    case IM_A_TEAPOT;
    case UNPROCESSABLE_ENTITY;
    case LOCKED;
    case FAILED_DEPENDENCY;
    case UNORDERED_COLLECTION;
    case UPGRADE_REQUIRED;
    case PRECONDITION_REQUIRED;
    case TOO_MANY_REQUESTS;
    case REQUEST_HEADER_FIELDS_TOO_LARGE;
    case UNAVAILABLE_FOR_LEGAL_REASONS;
    case INTERNAL_SERVER_ERROR;
    case NOT_IMPLEMENTED;
    case BAD_GATEWAY;
    case SERVICE_UNAVAILABLE;
    case GATEWAY_TIMEOUT;
    case HTTP_VERSION_NOT_SUPPORTED;
    case VARIANT_ALSO_NEGOTIATES;
    case INSUFFICIENT_STORAGE;
    case LOOP_DETECTED;
    case NETWORK_AUTHENTICATION_REQUIRED;

    public static function tryFromHTTPCode(int $code): ?self
    {
        return match ($code) {
            100 => self::CONTINUE,
            101 => self::SWITCHING_PROTOCOLS,
            102 => self::PROCESSING,
            200 => self::OK,
            201 => self::CREATED,
            202 => self::ACCEPTED,
            203 => self::NON_AUTHORITATIVE_INFORMATION,
            204 => self::NO_CONTENT,
            205 => self::RESET_CONTENT,
            206 => self::PARTIAL_CONTENT,
            207 => self::MULTI_STATUS,
            208 => self::ALREADY_REPORTED,
            300 => self::MULTIPLE_CHOICES,
            301 => self::MOVED_PERMANENTLY,
            302 => self::FOUND,
            303 => self::SEE_OTHER,
            304 => self::NOT_MODIFIED,
            305 => self::USE_PROXY,
            306 => self::SWITCH_PROXY,
            307 => self::TEMPORARY_REDIRECT,
            400 => self::BAD_REQUEST,
            401 => self::UNAUTHORIZED,
            402 => self::PAYMENT_REQUIRED,
            403 => self::FORBIDDEN,
            404 => self::NOT_FOUND,
            405 => self::METHOD_NOT_ALLOWED,
            406 => self::NOT_ACCEPTABLE,
            407 => self::PROXY_AUTHENTICATION_REQUIRED,
            408 => self::REQUEST_TIMEOUT,
            409 => self::CONFLICT,
            410 => self::GONE,
            411 => self::LENGTH_REQUIRED,
            412 => self::PRECONDITION_FAILED,
            413 => self::REQUEST_ENTITY_TOO_LARGE,
            414 => self::URI_TOO_LONG,
            415 => self::UNSUPPORTED_MEDIA_TYPE,
            416 => self::RANGE_NOT_SATISFIABLE,
            417 => self::EXPECTATION_FAILED,
            418 => self::IM_A_TEAPOT,
            422 => self::UNPROCESSABLE_ENTITY,
            423 => self::LOCKED,
            424 => self::FAILED_DEPENDENCY,
            425 => self::UNORDERED_COLLECTION,
            426 => self::UPGRADE_REQUIRED,
            428 => self::PRECONDITION_REQUIRED,
            429 => self::TOO_MANY_REQUESTS,
            431 => self::REQUEST_HEADER_FIELDS_TOO_LARGE,
            451 => self::UNAVAILABLE_FOR_LEGAL_REASONS,
            500 => self::INTERNAL_SERVER_ERROR,
            501 => self::NOT_IMPLEMENTED,
            502 => self::BAD_GATEWAY,
            503 => self::SERVICE_UNAVAILABLE,
            504 => self::GATEWAY_TIMEOUT,
            505 => self::HTTP_VERSION_NOT_SUPPORTED,
            506 => self::VARIANT_ALSO_NEGOTIATES,
            507 => self::INSUFFICIENT_STORAGE,
            508 => self::LOOP_DETECTED,
            511 => self::NETWORK_AUTHENTICATION_REQUIRED,
            default => null,
        };
    }

    public function getHTTPCode(): int
    {
        return match ($this) {
            self::CONTINUE => 100,
            self::SWITCHING_PROTOCOLS => 101,
            self::PROCESSING => 102,
            self::OK => 200,
            self::CREATED => 201,
            self::ACCEPTED => 202,
            self::NON_AUTHORITATIVE_INFORMATION => 203,
            self::NO_CONTENT => 204,
            self::RESET_CONTENT => 205,
            self::PARTIAL_CONTENT => 206,
            self::MULTI_STATUS => 207,
            self::ALREADY_REPORTED => 208,
            self::MULTIPLE_CHOICES => 300,
            self::MOVED_PERMANENTLY => 301,
            self::FOUND => 302,
            self::SEE_OTHER => 303,
            self::NOT_MODIFIED => 304,
            self::USE_PROXY => 305,
            self::SWITCH_PROXY => 306,
            self::TEMPORARY_REDIRECT => 307,
            self::BAD_REQUEST => 400,
            self::UNAUTHORIZED => 401,
            self::PAYMENT_REQUIRED => 402,
            self::FORBIDDEN => 403,
            self::NOT_FOUND => 404,
            self::METHOD_NOT_ALLOWED => 405,
            self::NOT_ACCEPTABLE => 406,
            self::PROXY_AUTHENTICATION_REQUIRED => 407,
            self::REQUEST_TIMEOUT => 408,
            self::CONFLICT => 409,
            self::GONE => 410,
            self::LENGTH_REQUIRED => 411,
            self::PRECONDITION_FAILED => 412,
            self::REQUEST_ENTITY_TOO_LARGE => 413,
            self::URI_TOO_LONG => 414,
            self::UNSUPPORTED_MEDIA_TYPE => 415,
            self::RANGE_NOT_SATISFIABLE => 416,
            self::EXPECTATION_FAILED => 417,
            self::IM_A_TEAPOT => 418,
            self::UNPROCESSABLE_ENTITY => 422,
            self::LOCKED => 423,
            self::FAILED_DEPENDENCY => 424,
            self::UNORDERED_COLLECTION => 425,
            self::UPGRADE_REQUIRED => 426,
            self::PRECONDITION_REQUIRED => 428,
            self::TOO_MANY_REQUESTS => 429,
            self::REQUEST_HEADER_FIELDS_TOO_LARGE => 431,
            self::UNAVAILABLE_FOR_LEGAL_REASONS => 451,
            self::INTERNAL_SERVER_ERROR => 500,
            self::NOT_IMPLEMENTED => 501,
            self::BAD_GATEWAY => 502,
            self::SERVICE_UNAVAILABLE => 503,
            self::GATEWAY_TIMEOUT => 504,
            self::HTTP_VERSION_NOT_SUPPORTED => 505,
            self::VARIANT_ALSO_NEGOTIATES => 506,
            self::INSUFFICIENT_STORAGE => 507,
            self::LOOP_DETECTED => 508,
            self::NETWORK_AUTHENTICATION_REQUIRED => 511,
        };
    }

    public function isSuccessful(): bool
    {
        $httpCode = $this->getHTTPCode();
        return $httpCode < 400 || $httpCode >= 600;
    }

    /**
     * @return int a value suitable for returning as an exit code in console applications.
     */
    public function getConsoleCode(): int
    {
        return $this->isSuccessful() ? 0 : 1;
    }

    public function getPhrase(): string
    {
        return match ($this) {
            self::CONTINUE => 'Continue',
            self::SWITCHING_PROTOCOLS => 'Switching Protocols',
            self::PROCESSING => 'Processing',
            self::OK => 'OK',
            self::CREATED => 'Created',
            self::ACCEPTED => 'Accepted',
            self::NON_AUTHORITATIVE_INFORMATION => 'Non-Authoritative Information',
            self::NO_CONTENT => 'No Content',
            self::RESET_CONTENT => 'Reset Content',
            self::PARTIAL_CONTENT => 'Partial Content',
            self::MULTI_STATUS => 'Multi-Status',
            self::ALREADY_REPORTED => 'Already Reported',
            self::MULTIPLE_CHOICES => 'Multiple Choices',
            self::MOVED_PERMANENTLY => 'Moved Permanently',
            self::FOUND => 'Found',
            self::SEE_OTHER => 'See Other',
            self::NOT_MODIFIED => 'Not Modified',
            self::USE_PROXY => 'Use Proxy',
            self::SWITCH_PROXY => 'Switch Proxy',
            self::TEMPORARY_REDIRECT => 'Temporary Redirect',
            self::BAD_REQUEST => 'Bad Request',
            self::UNAUTHORIZED => 'Unauthorized',
            self::PAYMENT_REQUIRED => 'Payment Required',
            self::FORBIDDEN => 'Forbidden',
            self::NOT_FOUND => 'Not Found',
            self::METHOD_NOT_ALLOWED => 'Method Not Allowed',
            self::NOT_ACCEPTABLE => 'Not Acceptable',
            self::PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required',
            self::REQUEST_TIMEOUT => 'Request Timeout',
            self::CONFLICT => 'Conflict',
            self::GONE => 'Gone',
            self::LENGTH_REQUIRED => 'Length Required',
            self::PRECONDITION_FAILED => 'Precondition Failed',
            self::REQUEST_ENTITY_TOO_LARGE => 'Request Entity Too Large',
            self::URI_TOO_LONG => 'URI Too Long',
            self::UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type',
            self::RANGE_NOT_SATISFIABLE => 'Range Not Satisfiable',
            self::EXPECTATION_FAILED => 'Expectation Failed',
            self::IM_A_TEAPOT => "I'm a Teapot",
            self::UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
            self::LOCKED => 'Locked',
            self::FAILED_DEPENDENCY => 'Failed Dependency',
            self::UNORDERED_COLLECTION => 'Unordered Collection',
            self::UPGRADE_REQUIRED => 'Upgrade Required',
            self::PRECONDITION_REQUIRED => 'Precondition Required',
            self::TOO_MANY_REQUESTS => 'Too Many Requests',
            self::REQUEST_HEADER_FIELDS_TOO_LARGE => 'Request Header Fields Too Large',
            self::UNAVAILABLE_FOR_LEGAL_REASONS => 'Unavailable For Legal Reasons',
            self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
            self::NOT_IMPLEMENTED => 'Not Implemented',
            self::BAD_GATEWAY => 'Bad Gateway',
            self::SERVICE_UNAVAILABLE => 'Service Unavailable',
            self::GATEWAY_TIMEOUT => 'Gateway Timeout',
            self::HTTP_VERSION_NOT_SUPPORTED => 'HTTP Version Not Supported',
            self::VARIANT_ALSO_NEGOTIATES => 'Variant Also Negotiates',
            self::INSUFFICIENT_STORAGE => 'Insufficient Storage',
            self::LOOP_DETECTED => 'Loop Detected',
            self::NETWORK_AUTHENTICATION_REQUIRED => 'Network Authentication Required',
        };
    }
}
