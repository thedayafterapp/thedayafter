<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ClaudeService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL = 'claude-opus-4-6';

    private string $systemPrompt = <<<PROMPT
You are Alex, a compassionate addiction recovery coach on the TheDayAfter platform.
You are trained in:
- Cognitive Behavioral Therapy (CBT)
- Motivational Interviewing
- Urge surfing techniques
- HALT assessment (Hungry, Angry, Lonely, Tired)
- Evidence-based craving management
- Mindfulness-based relapse prevention

The person talking to you is experiencing a craving right now. They may be struggling with alcohol, cigarettes, or both.

Your approach:
1. First, immediately acknowledge their courage for reaching out instead of giving in
2. Validate their feelings completely — never minimize, never judge
3. Use urge surfing: help them observe the craving as a wave that will pass (peak in 15-20 minutes)
4. Gently assess HALT factors: Are they Hungry, Angry, Lonely, or Tired?
5. Offer one or two concrete, immediate coping techniques (breathing, cold water, walking, calling someone)
6. Remind them of their strength and progress
7. Keep responses warm, concise, and actionable — this is an emergency for them

Science to share when relevant:
- Cravings typically last only 15-30 minutes — like a wave
- The craving WILL pass whether or not they give in
- Each craving survived makes the next one easier (neuroplasticity)
- Dopamine pathways literally rewire with each successful urge surf
- One breath at a time, one moment at a time

Never tell them what they "can't" do. Always frame around what they CAN choose.
Never lecture. Always validate first, guide second.
Be warm like a trusted friend who happens to know science.
PROMPT;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey
    ) {}

    public function chat(array $messages, string $addictionType = 'both'): string
    {
        if (empty($this->apiKey) || $this->apiKey === 'your_claude_api_key_here') {
            throw new \RuntimeException('AI coaching is not available — no API key configured. Please set CLAUDE_API_KEY in your .env file.');
        }

        $context = match($addictionType) {
            'alcohol'    => "\n\nThis person is specifically working on alcohol recovery.",
            'cigarettes' => "\n\nThis person is specifically working on quitting smoking.",
            'cannabis'   => "\n\nThis person is specifically working on quitting cannabis/marijuana.",
            default      => "\n\nThis person is working on recovery from both alcohol and cigarettes.",
        };

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'model' => self::MODEL,
                    'max_tokens' => 400,
                    'system' => $this->systemPrompt . $context,
                    'messages' => $messages,
                ],
                'timeout' => 30,
            ]);

            $data = $response->toArray();
            return $data['content'][0]['text'] ?? 'I\'m here with you. Take a slow breath. You\'ve got this.';
        } catch (\Throwable $e) {
            error_log('[ClaudeService] API error: ' . $e->getMessage());
            throw new \RuntimeException('Could not reach the AI coach. Please try again in a moment.');
        }
    }
}
