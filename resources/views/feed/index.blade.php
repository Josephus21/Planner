@extends('layouts.dashboard')

@section('content')
@php
  $me = auth()->user();
@endphp

<div class="page-heading d-flex align-items-center justify-content-between">
  <div>
    <h3 class="mb-0">Feed</h3>
    <small class="text-muted">Company news, updates, and conversations</small>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success mt-3">{{ session('success') }}</div>
@endif
@if($errors->any())
  <div class="alert alert-danger mt-3">{{ $errors->first() }}</div>
@endif

<div class="page-content">
<section class="section">

  <div class="feed-wrap">

  
    {{-- ============= COMPOSER (FB STYLE) ============= --}}
    <div class="fb-card mb-3">
      <div class="fb-card-body">
        <form method="POST" action="{{ route('feed.store') }}" enctype="multipart/form-data" class="composer">
          @csrf

          <div class="composer-top">
            <div class="avatar-circle">
              {{ strtoupper(substr($me->name ?? 'U', 0, 1)) }}
            </div>

            <button type="button" class="composer-input" data-bs-toggle="collapse" data-bs-target="#composerCollapse">
              What's on your mind, {{ explode(' ', $me->name ?? 'User')[0] }}?
            </button>
          </div>

          <div class="collapse mt-3" id="composerCollapse">
            <textarea name="body" class="form-control fb-textarea" rows="3" placeholder="Write something..." required>{{ old('body') }}</textarea>

            <div class="d-flex flex-wrap align-items-center justify-content-between mt-2 gap-2">
              <div class="d-flex flex-wrap gap-2">
                <label class="btn btn-light fb-btn-soft mb-0">
                  <i class="bi bi-image"></i> Photo
                  <input type="file" name="image" class="d-none" accept="image/*">
                </label>

                {{-- Optional UI buttons (no backend yet) --}}
                <button type="button" class="btn btn-light fb-btn-soft" disabled>
                  <i class="bi bi-camera-video"></i> Video
                </button>
                <button type="button" class="btn btn-light fb-btn-soft" disabled>
                  <i class="bi bi-emoji-smile"></i> Feeling
                </button>
              </div>

              <button class="btn btn-primary px-4">
                Post
              </button>
            </div>
          </div>
        </form>

        <hr class="my-3">

        {{-- Quick actions row (like FB) --}}
        <div class="composer-actions">
          <button type="button" class="btn btn-light fb-action" data-bs-toggle="collapse" data-bs-target="#composerCollapse">
            <i class="bi bi-camera-video"></i> Live video
          </button>
          <button type="button" class="btn btn-light fb-action" data-bs-toggle="collapse" data-bs-target="#composerCollapse">
            <i class="bi bi-images"></i> Photo/video
          </button>
          <button type="button" class="btn btn-light fb-action" data-bs-toggle="collapse" data-bs-target="#composerCollapse">
            <i class="bi bi-emoji-smile"></i> Feeling/activity
          </button>
        </div>
      </div>
    </div>

    {{-- ============= POSTS ============= --}}
    @forelse($posts as $post)
      @php
        $reactionCounts = $post->reactions->groupBy('type')->map->count();
        $myReaction = $post->reactions->firstWhere('user_id', auth()->id())?->type;

        $totalReacts = $post->reactions->count();
        $totalComments = $post->comments->count();
      @endphp

      <div class="fb-card mb-3">
        <div class="fb-card-body">

          {{-- Header --}}
          <div class="post-header">
            <div class="avatar-circle">
              {{ strtoupper(substr($post->user->name ?? 'U', 0, 1)) }}
            </div>

            <div class="post-meta">
              <div class="post-author">{{ $post->user->name }}</div>
              <div class="post-time text-muted">{{ $post->created_at->diffForHumans() }}</div>
            </div>

            @php
  $me = auth()->user();
  $isOwner = ((int)$post->user_id === (int)$me->id);
  // show menu if owner OR developer/admin view (if you already pass roleTitle/canViewAll, use that)
  $canSeeMenu = $isOwner || (!empty($roleTitle) && in_array(strtolower(trim($roleTitle)), ['developer','admin'], true)) || (!empty($canViewAll) && $canViewAll);
@endphp

<div class="dropdown">
  <button class="btn btn-light btn-sm fb-icon-btn dropdown-toggle"
          type="button"
          data-bs-toggle="dropdown"
          aria-expanded="false"
          title="More">
    <i class="bi bi-three-dots"></i>
  </button>

  <ul class="dropdown-menu dropdown-menu-end">
    @if($canSeeMenu)
      <li>
        <form method="POST"
              action="{{ route('posts.destroy', $post) }}"
              class="js-delete-post-form"
              data-post-id="{{ $post->id }}"
              onsubmit="return confirm('Delete this post?')">
          @csrf
          @method('DELETE')
          <button type="submit" class="dropdown-item text-danger">
            <i class="bi bi-trash me-2"></i> Delete
          </button>
        </form>
      </li>
    @else
      <li>
        <span class="dropdown-item text-muted">
          <i class="bi bi-lock me-2"></i> No actions available
        </span>
      </li>
    @endif
  </ul>
</div>
          </div>

          {{-- Body --}}
          <div class="post-body mt-2" style="white-space: pre-wrap;">{{ $post->body }}</div>

          {{-- Image --}}
          @if($post->image_path)
            <div class="post-media mt-2">
              <img src="{{ asset('storage/'.$post->image_path) }}" class="img-fluid rounded fb-media-img">
            </div>
          @endif

          {{-- Counts --}}
          <div class="post-counts">
            <div class="text-muted small">
              @if($totalReacts > 0)
                <span class="me-2">
                  <i class="bi bi-hand-thumbs-up-fill"></i> {{ $totalReacts }}
                </span>
              @endif
            </div>
            <div class="text-muted small">
              @if($totalComments > 0)
                <span>{{ $totalComments }} comment{{ $totalComments > 1 ? 's' : '' }}</span>
              @endif
            </div>
          </div>

          <hr class="my-2">

          {{-- Action bar (Like / Comment / Share UI) --}}
          <div class="post-actions">
            <button type="button" class="btn btn-light fb-action w-100 js-like-open" data-post-id="{{ $post->id }}">
              <i class="bi bi-hand-thumbs-up"></i> Like
            </button>

            <button type="button" class="btn btn-light fb-action w-100 js-focus-comment" data-post-id="{{ $post->id }}">
              <i class="bi bi-chat"></i> Comment
            </button>

            <button type="button" class="btn btn-light fb-action w-100" disabled>
              <i class="bi bi-share"></i> Share
            </button>
          </div>

          {{-- Hidden: reaction chips (FB-style quick reactions) --}}
          <div class="react-row mt-2" id="reactRow-{{ $post->id }}">
            @foreach(['like','love','haha','wow','sad','angry'] as $type)
              <form class="js-react-form d-inline-block"
                    method="POST"
                    action="{{ route('posts.react', $post) }}"
                    data-post-id="{{ $post->id }}"
                    data-type="{{ $type }}">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <button type="submit"
                        class="btn btn-sm js-react-btn react-chip {{ $myReaction === $type ? 'active' : '' }}"
                        data-type="{{ $type }}">
                  {{ strtoupper($type) }}
                  <span class="ms-1 js-react-count" data-type="{{ $type }}">
                    @if(($reactionCounts[$type] ?? 0) > 0)
                      ({{ $reactionCounts[$type] }})
                    @endif
                  </span>
                </button>
              </form>
            @endforeach
          </div>

          {{-- Comments --}}
          <div class="mt-3">
            <div class="d-flex align-items-center justify-content-between">
              <h6 class="mb-2">
                Comments (<span class="js-comment-count" data-post-id="{{ $post->id }}">{{ $post->comments->count() }}</span>)
              </h6>
            </div>

            <div class="js-comments-list" data-post-id="{{ $post->id }}">
              @foreach($post->comments as $c)
                <div class="comment-item">
                  <div class="avatar-circle avatar-sm">
                    {{ strtoupper(substr($c->user->name ?? 'U', 0, 1)) }}
                  </div>
                  <div class="comment-bubble">
                    <div class="comment-author">{{ $c->user->name }}</div>
                    <div class="comment-text" style="white-space: pre-wrap;">{{ $c->body }}</div>
                    <div class="comment-time text-muted">{{ $c->created_at->diffForHumans() }}</div>
                  </div>
                </div>
              @endforeach
            </div>

            <form class="js-comment-form mt-2"
                  method="POST"
                  action="{{ route('posts.comments.store', $post) }}"
                  data-post-id="{{ $post->id }}">
              @csrf

              <div class="comment-compose">
                <div class="avatar-circle avatar-sm">
                  {{ strtoupper(substr($me->name ?? 'U', 0, 1)) }}
                </div>

                <div class="input-group">
                  <input type="text" name="body" class="form-control js-comment-input"
                         placeholder="Write a comment..." required>
                  <button class="btn btn-outline-secondary" type="submit">
                    <i class="bi bi-send"></i>
                  </button>
                </div>
              </div>
            </form>

          </div>

        </div>
      </div>
    @empty
      <div class="fb-card">
        <div class="fb-card-body">
          <div class="alert alert-secondary mb-0">No posts yet.</div>
        </div>
      </div>
    @endforelse

    <div class="mt-3">
      {{ $posts->links() }}
    </div>

  </div>

</section>
</div>
@endsection

@push('styles')
<style>
  /* === FB-ish layout === */
  .feed-wrap{ max-width: 780px; margin: 0 auto; }
  .fb-card{
    background:#fff;
    border:1px solid rgba(0,0,0,.08);
    border-radius:14px;
    box-shadow:0 1px 2px rgba(0,0,0,.05);
    overflow:hidden;
  }
  .fb-card-body{ padding:16px; }

  .avatar-circle{
    width:44px; height:44px; border-radius:50%;
    background:#e9ecef; display:flex; align-items:center; justify-content:center;
    font-weight:700; color:#495057; flex:0 0 auto;
  }
  .avatar-sm{ width:34px; height:34px; font-size:12px; }

  /* Stories */
  .stories-row{
    display:flex; gap:10px; overflow-x:auto; padding-bottom:6px;
    scrollbar-width: thin;
  }
  .stories-row::-webkit-scrollbar{ height:8px; }
  .story{
    width:120px; flex:0 0 auto; cursor:pointer;
  }
  .story-cover{
    height:190px; border-radius:14px;
    background:linear-gradient(180deg,#adb5bd,#495057);
    position:relative; overflow:hidden;
  }
  .story-name{
    margin-top:8px; font-weight:600; font-size:13px;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    text-align:center;
  }
  .story-avatar{
    position:absolute; top:10px; left:10px;
    width:34px; height:34px; border-radius:50%;
    background:#0d6efd; display:flex; align-items:center; justify-content:center;
    padding:2px;
  }
  .story-avatar-inner{
    width:100%; height:100%; border-radius:50%;
    background:#e9ecef; display:flex; align-items:center; justify-content:center;
    font-weight:700;
  }
  .create-story .story-cover{
    background:linear-gradient(180deg,#f8f9fa,#dee2e6);
    display:flex; align-items:flex-end; justify-content:center;
  }
  .story-plus{
    width:44px; height:44px; border-radius:50%;
    background:#0d6efd; color:#fff;
    display:flex; align-items:center; justify-content:center;
    transform:translateY(18px);
    box-shadow:0 8px 18px rgba(13,110,253,.25);
  }

  /* Composer */
  .composer-top{ display:flex; gap:12px; align-items:center; }
  .composer-input{
    flex:1; text-align:left;
    border:0; background:#f1f3f5;
    border-radius:999px; padding:12px 16px;
    color:#495057;
  }
  .composer-input:hover{ background:#e9ecef; }
  .fb-textarea{ border-radius:12px; }
  .fb-btn-soft{ border-radius:10px; }

  .composer-actions{ display:flex; gap:10px; flex-wrap:wrap; }
  .fb-action{
    border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    gap:8px;
  }

  /* Post */
  .post-header{ display:flex; gap:12px; align-items:center; }
  .post-meta{ line-height:1.1; }
  .post-author{ font-weight:700; }
  .post-time{ font-size:12px; }
  .fb-icon-btn{ border-radius:10px; }

  .fb-media-img{ width:100%; max-height:520px; object-fit:cover; }

  .post-counts{
    display:flex; justify-content:space-between; align-items:center;
    margin-top:10px;
  }
  .post-actions{
    display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;
  }

  /* Reaction chips */
  .react-row{ display:flex; gap:8px; flex-wrap:wrap; }
  .react-chip{
    border:1px solid rgba(0,0,0,.1);
    background:#f8f9fa;
    border-radius:999px;
  }
  .react-chip.active{
    background:#0d6efd; color:#fff; border-color:#0d6efd;
  }

  /* Comments */
  .comment-item{ display:flex; gap:10px; align-items:flex-start; margin-bottom:10px; }
  .comment-bubble{
    background:#f1f3f5; border-radius:14px; padding:10px 12px; flex:1;
  }
  .comment-author{ font-weight:700; font-size:13px; }
  .comment-text{ margin-top:2px; }
  .comment-time{ font-size:11px; margin-top:4px; }
  .comment-compose{ display:flex; gap:10px; align-items:center; }
</style>
@endpush

@push('scripts')
<script>
  // UI: show reaction row when clicking Like (optional)
  document.addEventListener('click', function(e){
    const btn = e.target.closest('.js-like-open');
    if(!btn) return;
    const postId = btn.getAttribute('data-post-id');
    const row = document.getElementById('reactRow-' + postId);
    if(row) row.scrollIntoView({block:'nearest'});
  });

  // UI: focus comment input
  document.addEventListener('click', function(e){
    const btn = e.target.closest('.js-focus-comment');
    if(!btn) return;
    const postId = btn.getAttribute('data-post-id');
    const form = document.querySelector(`.js-comment-form[data-post-id="${postId}"]`);
    const input = form ? form.querySelector('.js-comment-input') : null;
    if(input) input.focus();
  });

  function csrfToken() {
    const el = document.querySelector('meta[name="csrf-token"]');
    return el ? el.getAttribute('content') : '';
  }

  async function postJson(url, formData) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json',
      },
      body: formData
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const msg =
        data?.message ||
        (data?.errors ? Object.values(data.errors)[0][0] : 'Request failed.');
      throw new Error(msg);
    }
    return data;
  }

  // ====== REACTIONS AJAX ======
  document.addEventListener('submit', async function(e) {
    const form = e.target.closest('.js-react-form');
    if (!form) return;

    e.preventDefault();

    try {
      const url = form.getAttribute('action');
      const fd = new FormData(form);

      const data = await postJson(url, fd);

      const postId = data.post_id;
      const counts = data.counts || {};
      const myReaction = data.myReaction; // null or "like"

      const allForms = document.querySelectorAll(`.js-react-form[data-post-id="${postId}"]`);
      allForms.forEach(f => {
        const type = f.getAttribute('data-type');
        const btn = f.querySelector('.js-react-btn');
        const countEl = f.querySelector(`.js-react-count[data-type="${type}"]`);

        if (btn) {
          btn.classList.remove('active');
          if (myReaction === type) btn.classList.add('active');
        }

        const n = Number(counts[type] || 0);
        if (countEl) {
          countEl.textContent = n > 0 ? `(${n})` : '';
        }
      });
    } catch (err) {
      alert(err.message || 'Reaction failed.');
    }
  });

  // ====== COMMENTS AJAX ======
  document.addEventListener('submit', async function(e) {
    const form = e.target.closest('.js-comment-form');
    if (!form) return;

    e.preventDefault();

    try {
      const url = form.getAttribute('action');
      const postId = form.getAttribute('data-post-id');
      const fd = new FormData(form);

      const data = await postJson(url, fd);

      const list = document.querySelector(`.js-comments-list[data-post-id="${postId}"]`);
      if (list && data.comment) {
        const div = document.createElement('div');
        div.className = 'comment-item';
        div.innerHTML = `
          <div class="avatar-circle avatar-sm">${escapeHtml((data.comment.user_name||'U').substring(0,1).toUpperCase())}</div>
          <div class="comment-bubble">
            <div class="comment-author">${escapeHtml(data.comment.user_name)}</div>
            <div class="comment-text" style="white-space: pre-wrap;">${escapeHtml(data.comment.body)}</div>
            <div class="comment-time text-muted">${escapeHtml(data.comment.created_human)}</div>
          </div>
        `;
        list.prepend(div);
      }

      const countEl = document.querySelector(`.js-comment-count[data-post-id="${postId}"]`);
      if (countEl && typeof data.comment_count !== 'undefined') {
        countEl.textContent = data.comment_count;
      }

      const input = form.querySelector('input[name="body"]');
      if (input) input.value = '';
    } catch (err) {
      alert(err.message || 'Comment failed.');
    }
  });

  function escapeHtml(str) {
    return String(str ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }
</script>
@endpush