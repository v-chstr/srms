<x-layouts.app title="Announcements">

    <x-layouts.page-header title="Announcements" :subtitle="$subtitle" />

    <x-announcements.management
        :announcements="$announcements"
        :archivedAnnouncements="$archivedAnnouncements"
        :courses="$courses"
        :filterRoute="$filterRoute"
        :storeRoute="$storeRoute"
        :manageBaseUrl="$manageBaseUrl"
        :ownerId="$ownerId"
    />

</x-layouts.app>
