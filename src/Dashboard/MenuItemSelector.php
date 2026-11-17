<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;

/**
 * Marks the main-menu item for $key as selected.
 *
 * The menu's counters hold positions in the full configured menu, but EasyAdmin's main menu only
 * contains the items the current user may see (permissions, "view as roles"). With items hidden the
 * position can point past the end ("Undefined array key 54", then "setSelected() on null") or at a
 * different item, so the item that links to $key wins; the position is only a fallback for keys
 * that are not URL paths.
 */
final class MenuItemSelector
{
    /**
     * @param array<int, MenuItemDto> $menuItems
     */
    public static function select(array $menuItems, string $key, ?int $index): void
    {
        if (null === $index) {
            return;
        }

        foreach ($menuItems as $item) {
            $linkUrl = $item->getLinkUrl();
            if (null !== $linkUrl && parse_url($linkUrl, \PHP_URL_PATH) === $key) {
                $item->setSelected(true);

                return;
            }
        }

        if (!str_starts_with($key, '/')) {
            ($menuItems[$index] ?? null)?->setSelected(true);
        }
    }
}
