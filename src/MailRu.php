<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 09.02.19
 * Time: 13:35
 */

namespace alexsisukin\PromoPult;


use GuzzleHttp\Client;

class MailRu
{
    /** @var string */
    private $email;
    /** @var string */
    private $domain;
    /** @var string */
    private $pass;
    /** @var string */
    private $login;
    /** @var Client */
    private $httpClient;
    private $baseHeaders = [
        "Accept-Encoding" => "gzip, deflate",
        "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
        "Accept-Language" => "ru,en;q=0.9,ja;q=0.8,en-US;q=0.7,nl;q=0.6",
        "User-Agent" => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) " .
            "Ubuntu Chromium/71.0.3578.98 Chrome/71.0.3578.98 Safari/537.36",
    ];
    /** @var int Максимальное количество реквестов */
    private $maxPage = 1;


    /** @var string */
    private $pageContent;

    public function __construct()
    {
        $this->httpClient = new Client(['cookies' => true]);
    }

    /**
     * Логиним пользователя
     * @throws \Exception
     */
    public function auth()
    {
        $response = $this->httpClient->post('https://auth.mail.ru/cgi-bin/auth', [
            'headers' => $this->baseHeaders,
            'form_params' => [
                'mhost' => 'm.mail.ru',
                'Login' => $this->login,
                'Domain' => $this->domain,
                'Password' => $this->pass
            ],
        ]);
        $this->pageContent = $response->getBody()->getContents();
        if (!preg_match('~auth\.mail\.ru\/cgi-bin\/logout~ui', $this->pageContent)) {
            throw new \Exception('Не удалось авторизоваться');
        }
        $this->setMaxPage();
    }

    /**
     *  Получение максимального количества страниц
     */
    private function setMaxPage()
    {
        $pattern = '~<a href="\/messages\/inbox\?page=(\d+?)&.+">\d+?<\/a>~ui';
        preg_match_all($pattern, $this->pageContent, $findResult);
        if (!isset($findResult['1'])) {
            return;
        }
        $this->maxPage = (int)max($findResult['1']);

    }

    /**
     * Паралельные запросы на получение всех страниц почты, результат массив subject
     * @return array
     * @throws \Throwable
     */
    public function getSubjects()
    {
        for ($i = 1; $i <= $this->maxPage; $i++) {
            $requestArr[] = $this->httpClient->getAsync('https://m.mail.ru/messages/inbox?page=' . $i, [
                'headers' => $this->baseHeaders,
            ]);
        }
        $responses = \GuzzleHttp\Promise\unwrap($requestArr);
        $subjects = [];
        /** @var \GuzzleHttp\Psr7\Response $response */
        foreach ($responses as $response) {
            $this->pageContent = $response->getBody()->getContents();
            $subjects = array_merge($subjects, $this->parserSubject());
        }
        return $subjects;
    }

    /**
     * Парсим на текущей странице все subject
     * @return array
     */
    public function parserSubject()
    {
        preg_match_all('~<span class="messageline__subject">([\w\W.]+?)<\/span>~', $this->pageContent, $result);
        if (isset($result['1'])) {
            return array_map(function ($subject) {
                return [trim($subject)];
            }, $result['1']);
        }
        return [];
    }


    /**
     * @param string $email
     * @throws \Exception
     */
    public function setEmail($email)
    {
        $this->email = filter_var($email, FILTER_VALIDATE_EMAIL);
        if (!$this->email) {
            throw new \Exception('Введите корректную почту');
        }
        $this->setLoginAndDomain($email);
    }

    /**
     * @param string $pass
     * @throws \Exception
     */
    public function setPass($pass)
    {
        if (empty($pass)) {
            throw new \Exception('пароль не может быть пустым');
        }
        $this->pass = $pass;
    }

    /**
     * @param string $email
     * @throws \Exception
     */
    private function setLoginAndDomain($email)
    {
        if ($position = strripos($email, '@')) {
            $this->domain = mb_substr($email, $position + 1);
            $this->login = mb_substr($email, 0, $position);
            return;
        }
        throw new \Exception('Логин и домен почты не найдены');

    }

}