<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use Playtini\EasyAdminHelperBundle\Dashboard\MenuItemSelector;
use PHPUnit\Framework\TestCase;

final class MenuItemSelectorTest extends TestCase
{
    public function testSelectsTheItemLinkingToTheKeyWhenItsPositionShifted(): void
    {
        // The configured menu had the Doc item at 3, but one item above it is hidden for this user.
        $items = [self::item('/admin'), self::item('/admin/log'), self::item('/admin/doc')];

        MenuItemSelector::select($items, '/admin/doc', 3);

        self::assertSame([false, false, true], self::selected($items));
    }

    public function testIndexPointingAtAnotherItemDoesNotSelectIt(): void
    {
        $items = [self::item('/admin'), self::item('/admin/log'), self::item('/admin/view-as')];

        MenuItemSelector::select($items, '/admin/doc', 1);

        self::assertSame([false, false, false], self::selected($items));
    }

    public function testIndexPastTheEndOfAFilteredMenuIsIgnored(): void
    {
        $items = [self::item('/admin')];

        MenuItemSelector::select($items, '/admin/doc', 54);

        self::assertSame([false], self::selected($items));
    }

    public function testMatchesIgnoringTheQueryString(): void
    {
        $items = [self::item('/admin'), self::item('/admin/doc?tab=all')];

        MenuItemSelector::select($items, '/admin/doc', 1);

        self::assertSame([false, true], self::selected($items));
    }

    public function testNonPathKeyFallsBackToThePosition(): void
    {
        $items = [self::item('/admin'), self::item('/admin/tools')];

        MenuItemSelector::select($items, 'tools', 1);

        self::assertSame([false, true], self::selected($items));
    }

    public function testLinkMatchIsSelectedEvenWithoutACounter(): void
    {
        $items = [self::item('/admin'), self::item('/admin/doc')];

        MenuItemSelector::select($items, '/admin/doc', null);

        self::assertSame([false, true], self::selected($items));
    }

    public function testUnknownKeySelectsNothing(): void
    {
        $items = [self::item('/admin'), self::item('/admin/doc')];

        MenuItemSelector::select($items, '/admin/unknown', null);
        MenuItemSelector::select($items, 'unknown', null);

        self::assertSame([false, false], self::selected($items));
    }

    private static function item(string $url): MenuItemDto
    {
        $item = new MenuItemDto();
        $item->setLinkUrl($url);

        return $item;
    }

    /**
     * @param array<int, MenuItemDto> $items
     *
     * @return list<bool>
     */
    private static function selected(array $items): array
    {
        return array_map(static fn (MenuItemDto $item): bool => $item->isSelected(), array_values($items));
    }
}
