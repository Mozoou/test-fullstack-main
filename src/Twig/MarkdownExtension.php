<?php

namespace App\Twig;

use Parsedown;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown', [$this, 'parseMarkdown'], ['is_safe' => ['html']]),
        ];
    }

    public function parseMarkdown(string $text): string
    {
        $parsedown = new Parsedown();
        $html = $parsedown->text($text);

        // Ajouter les IDs aux titres
        $html = $this->addHeadingIds($html);

        return $html;
    }

    private function addHeadingIds(string $html): string
    {
        return preg_replace_callback(
            '/<h([1-6])([^>]*)>(.*?)<\/h\1>/i',
            function ($matches) {
                $level = $matches[1];
                $attrs = $matches[2];
                $text = $matches[3];

                // Générer un ID à partir du texte
                $id = $this->slugify($text);

                // Vérifier s'il y a déjà un ID
                if (preg_match('/id=["\']([^"\']*)["\']/', $attrs)) {
                    return $matches[0]; // Garder l'ID existant
                }

                return "<h{$level}{$attrs} id=\"{$id}\">$text</h{$level}>";
            },
            $html
        );
    }

    private function slugify(string $text): string
    {
        // Supprimer les balises HTML
        $text = strip_tags($text);

        // Convertir en minuscules
        $text = strtolower($text);

        // Remplacer les espaces et caractères spéciaux par des tirets
        $text = preg_replace('/[^\w\s-]/u', '', $text);
        $text = preg_replace('/[\s_]+/', '-', $text);
        $text = preg_replace('/^-+|-+$/', '', $text);

        return $text;
    }
}
