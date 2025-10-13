(function(){
  const { useState, useEffect } = React;
  
  function CommunityManagement() {
    const [posts, setPosts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [pagination, setPagination] = useState({ page: 1, limit: 10, total: 0, pages: 0 });

    useEffect(() => {
      loadPosts();
    }, [pagination.page]);

    const loadPosts = async () => {
      try {
        setLoading(true);
        const response = await axios.get(`admin_api.php?action=community_posts&page=${pagination.page}&limit=${pagination.limit}`);
        if (response.data.success) {
          setPosts(response.data.data);
          setPagination(response.data.pagination);
        } else {
          setError(response.data.error || 'Failed to load community posts');
        }
      } catch (err) {
        setError('Failed to load community posts');
      } finally {
        setLoading(false);
      }
    };

    const handleDeletePost = async (postId) => {
      if (!confirm('Are you sure you want to delete this post?')) return;
      
      try {
        setError('');
        setSuccess('');
        const response = await axios.post('admin_api.php?action=delete_post', { post_id: postId });
        if (response.data.success) {
          setSuccess('Post deleted successfully');
          loadPosts();
        } else {
          setError(response.data.error || 'Failed to delete post');
        }
      } catch (err) {
        setError('Failed to delete post');
      }
    };

    return (
      React.createElement('div', null, [
        React.createElement('div', { key: 'header', className: 'card' }, [
          React.createElement('h3', { key: 'title' }, 'Community Management'),
          React.createElement('p', { key: 'desc' }, 'View and manage community posts and comments')
        ]),
        
        error ? React.createElement('div', { key: 'error', className: 'error' }, error) : null,
        success ? React.createElement('div', { key: 'success', className: 'success' }, success) : null,

        React.createElement('div', { key: 'table', className: 'card' }, [
          React.createElement('table', { key: 'posts-table' }, [
            React.createElement('thead', { key: 'head' }, 
              React.createElement('tr', { key: 'row' }, [
                React.createElement('th', { key: 'id' }, 'ID'),
                React.createElement('th', { key: 'creator' }, 'Creator'),
                React.createElement('th', { key: 'community' }, 'Community'),
                React.createElement('th', { key: 'caption' }, 'Content'),
                React.createElement('th', { key: 'photo' }, 'Photo'),
                React.createElement('th', { key: 'created' }, 'Created'),
                React.createElement('th', { key: 'actions' }, 'Actions')
              ])
            ),
            React.createElement('tbody', { key: 'body' },
              loading ? 
                React.createElement('tr', { key: 'loading' }, 
                  React.createElement('td', { colSpan: 7, style: { textAlign: 'center' } }, 'Loading...')
                ) :
                posts.map(post => 
                  React.createElement('tr', { key: post.id }, [
                    React.createElement('td', { key: 'id' }, post.id),
                    React.createElement('td', { key: 'creator' }, post.creator_name),
                    React.createElement('td', { key: 'community' }, post.community_name),
                    React.createElement('td', { key: 'caption' }, 
                      React.createElement('div', { 
                        style: { 
                          maxWidth: '200px', 
                          overflow: 'hidden', 
                          textOverflow: 'ellipsis',
                          whiteSpace: 'nowrap'
                        } 
                      }, post.caption || 'No content')
                    ),
                    React.createElement('td', { key: 'photo' }, post.photo ? 'Yes' : 'No'),
                    React.createElement('td', { key: 'created' }, new Date(post.created_at).toLocaleDateString()),
                    React.createElement('td', { key: 'actions' }, [
                      React.createElement('button', {
                        key: 'delete',
                        className: 'btn btn-danger',
                        onClick: () => handleDeletePost(post.id)
                      }, 'Delete')
                    ])
                  ])
                )
            )
          ])
        ]),

        pagination.pages > 1 ? React.createElement('div', { key: 'pagination', className: 'card' }, [
          React.createElement('div', { key: 'nav', className: 'row' }, [
            React.createElement('button', {
              key: 'prev',
              className: 'btn',
              disabled: pagination.page <= 1,
              onClick: () => setPagination({...pagination, page: pagination.page - 1})
            }, 'Previous'),
            React.createElement('span', { key: 'info', style: { margin: '0 10px', alignSelf: 'center' } }, 
              `Page ${pagination.page} of ${pagination.pages}`
            ),
            React.createElement('button', {
              key: 'next',
              className: 'btn',
              disabled: pagination.page >= pagination.pages,
              onClick: () => setPagination({...pagination, page: pagination.page + 1})
            }, 'Next')
          ])
        ]) : null
      ])
    );
  }
  
  window.Components = window.Components || {};
  window.Components.CommunityManagement = CommunityManagement;
})();
