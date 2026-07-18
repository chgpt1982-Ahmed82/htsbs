<?php

declare(strict_types=1);


use Exception;

class OpenAIClient
{
    /**
     * API Key
     */
    private string $apiKey;

    /**
     * Model
     */
    private string $model;

    /**
     * Endpoint
     */
    private string $endpoint =
        'https://api.openai.com/v1/responses';

    /**
     * Constructor
     */
    public function __construct(
        string $apiKey,
        string $model
    ) {

        $this->apiKey = trim($apiKey);

        $this->model = trim($model);

    }

    /*
    =====================================================
    Generate Lesson
    =====================================================
    */

    public function generate(
        string $systemPrompt,
        string $userPrompt
    ): array {

        $payload = [

            'model' => $this->model,

            'input' => [

                [

                    'role' => 'system',

                    'content' => [

                        [

                            'type' => 'input_text',

                            'text' => $systemPrompt

                        ]

                    ]

                ],

                [

                    'role' => 'user',

                    'content' => [

                        [

                            'type' => 'input_text',

                            'text' => $userPrompt

                        ]

                    ]

                ]

            ],

            'max_output_tokens' => 7000

        ];

        $response = $this->sendRequest(

            $payload

        );

        return $this->parseResponse(

            $response

        );

    }

    /*
    =====================================================
    Send Request
    =====================================================
    */

    private function sendRequest(
        array $payload
    ): array {

        $ch = curl_init();

        curl_setopt_array($ch, [

            CURLOPT_URL => $this->endpoint,

            CURLOPT_POST => true,

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_CONNECTTIMEOUT => 30,

            CURLOPT_TIMEOUT => 300,

            CURLOPT_HTTPHEADER => [

                'Authorization: Bearer ' . $this->apiKey,

                'Content-Type: application/json'

            ],

            CURLOPT_POSTFIELDS => json_encode(

                $payload,

                JSON_UNESCAPED_UNICODE

            )

        ]);

        $body = curl_exec($ch);

        $httpCode = curl_getinfo(

            $ch,

            CURLINFO_HTTP_CODE

        );

        if ($body === false) {

            $error = curl_error($ch);

            curl_close($ch);

            throw new Exception(

                'cURL Error: ' . $error

            );

        }

        curl_close($ch);

        if ($httpCode !== 200) {

            throw new Exception(

                "OpenAI Error ({$httpCode})\n\n{$body}"

            );

        }

        $json = json_decode(

            $body,

            true

        );

        if (

            json_last_error() !== JSON_ERROR_NONE

        ) {

            throw new Exception(

                'JSON Decode Error: '

                .

                json_last_error_msg()

            );

        }

        return $json;

    }
        /*
    =====================================================
    Parse Response
    =====================================================
    */

    private function parseResponse(
        array $response
    ): array {

        $tokens =

            $response['usage']['total_tokens']

            ??

            0;

        $text = '';

        foreach (

            $response['output'] ?? []

            as $item

        ) {

            if (

                ($item['type'] ?? '') !== 'message'

            ) {

                continue;

            }

            foreach (

                $item['content'] ?? []

                as $content

            ) {

                if (

                    ($content['type'] ?? '') === 'output_text'

                ) {

                    $text = trim(

                        $content['text']

                    );

                    break 2;

                }

            }

        }

        if ($text === '') {

            throw new Exception(

                'لم يتم استلام أي محتوى من OpenAI.'

            );

        }

        /*
        ==============================================
        Remove Markdown
        ==============================================
        */

        $text = preg_replace(

            '/^```json\s*/i',

            '',

            $text

        );

        $text = preg_replace(

            '/^```\s*/',

            '',

            $text

        );

        $text = preg_replace(

            '/\s*```$/',

            '',

            $text

        );

        $text = trim($text);

        /*
        ==============================================
        Validate JSON
        ==============================================
        */

        json_decode($text, true);

        if (

            json_last_error() !== JSON_ERROR_NONE

        ) {

            throw new Exception(

                'الناتج ليس JSON صالحاً: '

                .

                json_last_error_msg()

            );

        }

        /*
        ==============================================
        Return Result
        ==============================================
        */

        return [

            'text' => $text,

            'tokens_used' => (int)$tokens,

            'model' => $this->model,

            'raw_response' => $response

        ];

    }

}