<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextAlign;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Playtini\EasyAdminHelperBundle\Controller\Interfaces\ArchiveCrudControllerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Yaml\Yaml;
use function Symfony\Component\String\u;

class CrudField
{
    public static bool $disabled = false;

    public static function panel(string $label, int|string $cols = 12, ?string $icon = null): FormField
    {
        $cols = max(1, min(12, $cols));

        return FormField::addFieldset($label, $icon)->addCssClass(sprintf('field-form_column %s', is_int($cols) ? 'col-md-' . $cols : $cols));
    }

    public static function text(
        string $property,
        ?string $label = null,
        int $cols = 12,
        bool $required = false,
        ?bool $disabled = null,
        ?int $maxLength = null,
    ): TextField
    {
        $label ??= self::humanizeString($property);
        $disabled ??= self::$disabled;

        $result = TextField::new($property)
            ->setLabel($label)
            ->setColumns($cols)
            ->setRequired($required)
            ->setDisabled($disabled)
            ->setEmptyData('');

        if ($maxLength) {
            $result->setMaxLength($maxLength);
        }

        return $result;
    }

    public static function int(
        string $property,
        ?string $label = null,
        int $cols = 12,
        bool $required = false,
        ?bool $disabled = null,
        ?int $emptyData = 0,
    ): IntegerField
    {
        $label ??= self::humanizeString($property);
        $disabled ??= self::$disabled;

        return IntegerField::new($property)
            ->setLabel($label)
            ->setColumns($cols)
            ->setRequired($required)
            ->setDisabled($disabled)
            ->setEmptyData($emptyData);
    }

    public static function textarea(
        string $property,
        ?string $label = null,
        int $cols = 12,
        int $rows = 10,
        bool $required = false,
        ?bool $disabled = null,
        ?int $maxLength = null,
    ): TextareaField
    {
        $label ??= self::humanizeString($property);
        $disabled ??= self::$disabled;

        $result = TextareaField::new($property)
            ->setLabel($label)
            ->setColumns($cols)
            ->setNumOfRows($rows)
            ->setRequired($required)
            ->setDisabled($disabled)
            ->setEmptyData('');

        if ($maxLength) {
            $result->setMaxLength($maxLength);
        }

        return $result;
    }

    public static function dateMinutes(
        string $property,
        ?string $label = null,
        int $cols = 12,
        bool $required = false,
        ?bool $disabled = null,
    ): DateTimeField
    {
        $label ??= self::humanizeString($property);
        $disabled ??= self::$disabled;

        return DateTimeField::new($property)
            ->setFormat('YYYY-MM-dd HH:mm')
            ->setLabel($label)
            ->setColumns($cols)
            ->setRequired($required)
            ->setDisabled($disabled);
    }

    public static function association(
        string $property,
        ?string $label = null,
        int $cols = 12,
        bool $required = false,
        ?bool $disabled = null,
        bool $autocomplete = true,
    ): AssociationField
    {
        $label ??= self::humanizeString($property);
        $disabled ??= self::$disabled;

        $result = AssociationField::new($property)
            ->setLabel($label)
            ->setColumns($cols)
            ->setRequired($required)
            ->setDisabled($disabled);

        if ($autocomplete) {
            $result->autocomplete();
        }

        return $result;
    }

    public static function id(int $cols = 0): IdField
    {
        $result = IdField::new('id')
            ->setLabel('ID')
            ->setColumns($cols)
            ->hideWhenCreating()
            ->setDisabled()
            ->setRequired(false);

        if (!$cols) {
            $result->hideOnForm();
        }

        return $result;
    }

    public static function name(int $cols = 12): TextField
    {
        return TextField::new('name')
            ->setLabel('Name')
            ->setColumns($cols)
            ->setDisabled(self::$disabled)
            ->setRequired(!self::$disabled);
    }

    public static function createdAtDate(int $cols = 12): DateField
    {
        return DateField::new('createdAt')
            ->setFormat('YYYY-MM-dd HH:mm')
            ->setLabel('Created')
            ->setColumns($cols)
            ->setDisabled()
            ->setRequired(false)
            ->hideWhenCreating();
    }

    public static function createdAt(int $cols = 12): DateTimeField
    {
        return DateTimeField::new('createdAt')
            ->setFormat('YYYY-MM-dd HH:mm:ss')
            ->setLabel('Created')
            ->setColumns($cols)
            ->setDisabled()
            ->setRequired(false)
            ->hideWhenCreating();
    }

    public static function sentAt(int $cols = 12): DateTimeField
    {
        return DateTimeField::new('sentAt')
            ->setFormat('YYYY-MM-dd HH:mm:ss')
            ->setLabel('Sent')
            ->setColumns($cols)
            ->setDisabled()
            ->setRequired(false)
            ->hideWhenCreating();
    }

    public static function updatedAt(int $cols = 12): DateTimeField
    {
        return DateTimeField::new('updatedAt')
            ->setFormat('YYYY-MM-dd HH:mm:ss')
            ->setLabel('Updated')
            ->setColumns($cols)
            ->setDisabled()
            ->setRequired(false)
            ->hideWhenCreating();
    }

    public static function finishedAt(int $cols = 12): DateTimeField
    {
        return DateTimeField::new('finishedAt')
            ->setFormat('YYYY-MM-dd HH:mm:ss')
            ->setLabel('Finished')
            ->setColumns($cols)
            ->setDisabled()
            ->setRequired(false)
            ->hideWhenCreating();
    }

    public static function archivedAtDate(AbstractCrudController $controller, int $cols = 12): DateField
    {
        $field = DateField::new('archivedAt')
            ->setFormat('YYYY-MM-dd HH:mm')
            ->setLabel('Archived')
            ->setColumns($cols)
            ->hideWhenCreating()
            ->setRequired(false)
            ->setDisabled(self::$disabled);
        if ($controller instanceof ArchiveCrudControllerInterface && !$controller->isShownArchive()) {
            $field->hideOnIndex();
        }

        return $field;
    }

    public static function virtual(string $label, callable $callable): TextField
    {
        $field = TextField::new('virtual')
            ->setColumns(12)
            ->setRequired(false)
            ->setDisabled()
            ->setVirtual(true)
            ->setSortable(false)
            ->hideOnForm()
            ->formatValue($callable);

        if ('' !== $label) {
            $field->setLabel($label);
        }

        return $field;
    }

    public static function virtualInt(string $label, callable $callable): NumberField
    {
        return NumberField::new('virtualInt')
            ->setColumns(1)
            ->setRequired(false)
            ->setDisabled()
            ->setVirtual(true)
            ->setSortable(false)
            ->hideOnForm()
            ->setTextAlign(TextAlign::RIGHT)
            ->setLabel($label)
            ->formatValue($callable);
    }

    public static function nullableString(string $label, string $property = 'virtualString'): NumberField
    {
        return NumberField::new($property)
            ->setColumns(1)
            ->setRequired(false)
            ->setDisabled()
            ->setVirtual(true)
            ->setSortable(false)
            ->hideOnForm()
            ->setTextAlign(TextAlign::RIGHT)
            ->setLabel($label)
            ->formatValue(fn($val) => (string)$val);
    }

    public static function choices(string $property, array $choices, bool $isIndex): ChoiceField|TextField
    {
        if ($isIndex) {
            return self::virtual(
                $property,
                static fn($k, $v) => implode(',', PropertyAccess::createPropertyAccessor()->getValue($v, $property))
            );
        }

        return ChoiceField::new($property)
            ->setChoices($choices)
            ->allowMultipleChoices()
            ->setRequired(false);
    }

    public static function yaml(
        string $property,
        ?string $label = null,
        int $cols = 12,
        int $rows = 10,
        bool $isIndex = false,
        int $inline = 2,
        string|int $maxWidth = 500,
    ): YamlField|TextField
    {
        $label ??= self::humanizeString($property);

        if ($isIndex) {
            return self::virtual(
                $property,
                static fn($d, $v) => self::yamlDumpHtml(PropertyAccess::createPropertyAccessor()->getValue($v, $property), inline: $inline, maxWidth: $maxWidth)
            )->renderAsHtml()->setLabel(self::humanizeString($property));
        }

        return YamlField::new($property)
            ->setLabel($label)
            ->setColumns($cols)
            ->setNumOfRows($rows)
            ->setRequired(false);
    }

    private static function yamlDumpHtml(mixed $a, int $inline = 2, string|int $maxWidth = 500): string
    {
        if (is_int($maxWidth) || preg_match('#^\d+$#', $maxWidth)) {
            $maxWidth = sprintf('%spx', $maxWidth);
        }

        return sprintf(
            '<span style="white-space: pre; display: block; max-width: %s; overflow: hidden">%s</span>',
            $maxWidth,
            htmlspecialchars(Yaml::dump($a, inline: $inline))
        );
    }

    public static function uid(bool $truncate = false, int $cols = 12): TextField
    {
        return TextField::new('uid')
            ->setColumns($cols)
            ->setRequired(false)
            ->setDisabled()
            ->hideWhenCreating()
            ->formatValue(static fn($v) => sprintf('<span class="small">%s</span>', $truncate ? self::truncateEllipsis($v, 6) : $v));
    }

    public static function messageUid(bool $truncate = false, int $cols = 12): TextField
    {
        return TextField::new('messageUid')
            ->setLabel('Message')
            ->setColumns($cols)
            ->setRequired(false)
            ->setDisabled()
            ->hideWhenCreating()
            ->formatValue(static fn($v) => sprintf('<span class="small">%s</span>', $truncate ? self::truncateEllipsis($v, 6) : $v));
    }

    public static function externalUid(bool $truncate = false, int $cols = 12): TextField
    {
        return TextField::new('externalUid')
            ->setLabel('External')
            ->setColumns($cols)
            ->setRequired(false)
            ->setDisabled()
            ->hideWhenCreating()
            ->formatValue(static fn($v) => sprintf('<span class="small">%s</span>', $truncate ? self::truncateEllipsis($v, 6) : $v));
    }

    public static function uidEditable(bool $truncate = false): TextField
    {
        return TextField::new('uid')
            ->setColumns(12)
            ->setRequired(false)
            ->setDisabled(self::$disabled)
            ->formatValue(static fn($v) => sprintf('<span class="small">%s</span>', $truncate ? self::truncateEllipsis($v, 6) : $v));
    }

    public static function virtualRight(string $label, callable $callable): TextField
    {
        return self::virtual($label, $callable)->setTextAlign(TextAlign::RIGHT);
    }

    public static function comment(int $rows = 10, int $cols = 12): TextareaField
    {
        return TextareaField::new('comment')
            ->setColumns($cols)
            ->setRequired(false)
            ->setDisabled(self::$disabled)
            ->setNumOfRows($rows);
    }

    public static function commentShort(int $cols = 12): TextField
    {
        return TextField::new('comment')
            ->setColumns($cols)
            ->setRequired(false)
            ->setDisabled(self::$disabled);
    }

    public static function isEnabled(int $cols = 2): BooleanField
    {
        return BooleanField::new('isEnabled')
            ->setLabel('Enabled')
            ->setColumns($cols)
            ->setRequired(false)
            ->setDisabled(self::$disabled)
            ->setTemplatePath('easyadmin/fields/boolnull.html.twig');
    }

    private static function humanizeString(string $string): string
    {
        $uString = u($string);
        $upperString = $uString->upper()->toString();

        // this prevents humanizing all-uppercase labels (e.g. 'UUID' -> 'U u i d')
        // and other special labels which look better in uppercase
        if ($uString->toString() === $upperString) {
            return $upperString;
        }

        return $uString
            ->replaceMatches('/([A-Z])/', '_$1')
            ->replaceMatches('/[_\s]+/', ' ')
            ->trim()
            ->lower()
            ->title(true)
            ->toString();
    }

    private static function truncateEllipsis(?string $s, int $maxLength = 30, string $suffix = '&hellip;'): string
    {
        $s = (string) $s;

        if (mb_strlen($s) > $maxLength) {
            $s = mb_substr($s, 0, $maxLength).$suffix;
        }

        return $s;
    }
}
