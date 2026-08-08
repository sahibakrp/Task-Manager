import { Link } from 'react-router-dom';

export default function TaskCard({ task, onDelete }) {
  return (
    <div style={{ background: '#fff', padding: '1rem', borderRadius: '12px', boxShadow: '0 1px 10px rgba(0,0,0,.05)' }}>
      <h3 className="text-lg font-semibold text-gray-900">{task.title}</h3>
      <p className="mt-2 text-sm text-gray-600">{task.description || 'No description provided.'}</p>
      <p className="mt-3 text-sm text-gray-700">Status: {task.status}</p>
      <p className="text-sm text-gray-700">Priority: {task.priority}</p>
      <p className="text-sm text-gray-700">Due: {task.due_date || '—'}</p>
      <div className="mt-4 flex items-center gap-3">
        <Link
          to={`/edit/${task.id}`}
          className="text-sm font-semibold text-indigo-600 hover:text-indigo-800"
        >
          Edit
        </Link>
        {onDelete && (
          <button
            type="button"
            onClick={() => onDelete(task.id)}
            className="text-sm font-semibold text-red-600 hover:text-red-800"
          >
            Delete
          </button>
        )}
      </div>
    </div>
  );
}
