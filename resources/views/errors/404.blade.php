<x-layouts.error :title="'Page not found'">
    <x-errors.page
        code="404"
        title="Page not found"
        message="The page you are looking for does not exist, may have been moved, or you may not have permission to view it."
        :show-login="true"
        :show-retry="false"
    />
</x-layouts.error>
