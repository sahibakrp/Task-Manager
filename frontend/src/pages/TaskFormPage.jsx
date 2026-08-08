import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';

const initialState = {
  title: '',
  description: '',
  status: 'todo',
  priority: 'medium',
  due_date: ''
};

const inputClass =
  'w-full px-3.5 py-2.5 text-base rounded-lg border border-gray-300 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100';

export default function TaskFormPage() {
  const { authAxios } = useAuth();
  const { id } = useParams();
  const [task, setTask] = useState(initialState);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(Boolean(id));
  const [submitting, setSubmitting] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    if (!id) return;

    const loadTask = async () => {
      try {
        const response = await authAxios.get(`/tasks/${id}`);
        setTask(response.data);
      } catch (err) {
        setError(err.response?.data?.message || 'Unable to load task');
      } finally {
        setLoading(false);
      }
    };

    loadTask();
  }, [authAxios, id]);

  const handleChange = (key) => (event) => {
    setTask((prev) => ({ ...prev, [key]: event.target.value }));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    setSubmitting(true);
    try {
      if (id) {
        await authAxios.put(`/tasks/${id}`, task);
      } else {
        await authAxios.post('/tasks', task);
      }
      navigate('/');
    } catch (err) {
      setError(err.response?.data?.message || 'Unable to save task');
      setSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100 px-4 py-8">
      <div className="w-full max-w-xl bg-white rounded-2xl shadow-lg px-8 py-10">
        <h1 className="text-2xl font-bold text-gray-900 mb-6">
          {id ? 'Edit Task' : 'Create Task'}
        </h1>

        {error && (
          <p className="bg-red-50 text-red-600 text-sm border border-red-200 rounded-lg px-4 py-3 mb-5">
            {error}
          </p>
        )}

        {loading ? (
          <p className="text-gray-500 text-sm">Loading task…</p>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-5">
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                Title
              </label>
              <input
                value={task.title}
                onChange={handleChange('title')}
                className={inputClass}
                required
              />
            </div>

            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                Description
              </label>
              <textarea
                value={task.description}
                onChange={handleChange('description')}
                rows="4"
                className={`${inputClass} resize-none`}
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                  Status
                </label>
                <select
                  value={task.status}
                  onChange={handleChange('status')}
                  className={`${inputClass} bg-white`}
                >
                  <option value="todo">Todo</option>
                  <option value="in-progress">In Progress</option>
                  <option value="done">Done</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                  Priority
                </label>
                <select
                  value={task.priority}
                  onChange={handleChange('priority')}
                  className={`${inputClass} bg-white`}
                >
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                </select>
              </div>
            </div>

            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1.5">
                Due Date
              </label>
              <input
                value={task.due_date}
                onChange={handleChange('due_date')}
                type="date"
                className={inputClass}
              />
            </div>

            <button
              type="submit"
              disabled={submitting}
              className="w-full py-3 mt-1 text-base font-semibold text-white bg-indigo-600 rounded-lg transition hover:bg-indigo-700 disabled:bg-indigo-300 disabled:cursor-not-allowed"
            >
              {submitting ? (id ? 'Updating…' : 'Creating…') : id ? 'Update' : 'Create'}
            </button>
          </form>
        )}
      </div>
    </div>
  );
}