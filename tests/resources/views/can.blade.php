@can('create-post')
    <button class="btn btn-danger">Create Post</button>
@endcan

@can('edit-post',Back2Lobby\AccessControl\Tests\Models\Post::first())
    <button class="btn btn-danger">Edit Post</button>
@endcan