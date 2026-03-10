<?php

namespace App\Service;

class QuoteService
{
    private array $quotes = [
        ["Cravings are like waves — they peak, then they pass. You don't have to fight them, just ride them.", "Urge Surfing Research"],
        ["Every time you resist a craving, you literally rewire your brain. You are not just quitting — you are rebuilding.", "Neuroscience"],
        ["The chains of addiction are too light to be felt until they are too heavy to be broken. You broke yours.", "Warren Buffett (adapted)"],
        ["You didn't come this far to only come this far.", "Unknown"],
        ["Recovery is not a race. You don't have to feel guilty about how long the journey takes.", "Unknown"],
        ["One day at a time. If that's too much, one hour. If that's too much, one breath.", "AA Wisdom"],
        ["The greatest glory in living lies not in never falling, but in rising every time we fall.", "Nelson Mandela"],
        ["What you are doing right now is one of the hardest things a human being can do. Be proud of every single day.", "Recovery Science"],
        ["Your brain is literally healing and growing new neural pathways. Change is not just possible — it is already happening.", "Neuroplasticity Research"],
        ["Dopamine doesn't have to come from a bottle or a cigarette. Every achievement, every sunrise, every connection is dopamine you earned.", "Addiction Medicine"],
        ["The version of you that is free is not a fantasy. It's the real you, fighting to get out.", "Unknown"],
        ["Sobriety was the greatest gift I ever gave myself.", "Rob Lowe"],
        ["It does not matter how slowly you go as long as you do not stop.", "Confucius"],
        ["You are not broken. You are a human being doing one of the hardest things humans do.", "Recovery Community"],
        ["The opposite of addiction is not sobriety. The opposite of addiction is connection.", "Johann Hari"],
        ["Every morning you wake up clean is a morning you chose yourself. That matters enormously.", "Unknown"],
        ["Your worst day in recovery is better than your best day in addiction.", "Recovery Community"],
        ["Be patient with yourself. You are a work in progress, and progress is not linear.", "Unknown"],
        ["Healing is not linear. A setback does not erase what you've built. You know things now that you didn't before.", "Relapse Science"],
        ["The bravest thing you can do is ask for help. You already did.", "Unknown"],
    ];

    public function getDailyQuote(): array
    {
        $index = (int) date('z') % count($this->quotes);
        return [
            'text'   => $this->quotes[$index][0],
            'source' => $this->quotes[$index][1],
        ];
    }

    public function getRandomQuote(): array
    {
        $q = $this->quotes[array_rand($this->quotes)];
        return ['text' => $q[0], 'source' => $q[1]];
    }
}
