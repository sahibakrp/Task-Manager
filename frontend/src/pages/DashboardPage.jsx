import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import TaskCard from '../components/TaskCard';

export default function DashboardPage() {
  const { authAxios, user } = useAuth();
  const [tasks, setTasks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchTasks = async () => {
      try {
        const response = await authAxios.get('/tasks');
        setTasks(response.data.data || []);
      } catch (err) {
        setError(err.response?.data?.message || 'Unable to load tasks');
      } finally {
        setLoading(false);
      }
    };

    fetchTasks();
  }, [authAxios]);

  const handleDelete = async (taskId) => {
    if (!window.confirm('Delete this task?')) {
      return;
    }

    try {
      await authAxios.delete(`/tasks/${taskId}`);
      setTasks((prev) => prev.filter((task) => task.id !== taskId));
    } catch (err) {
      setError(err.response?.data?.message || 'Unable to delete task');
    }
  };

  return (
    <div className="min-h-screen bg-gray-100">
      <div className="max-w-5xl mx-auto px-4 py-10">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p className="text-gray-500 mt-1">Welcome back, {user?.name}</p>
          </div>
          <Link to="/create">
            <button className="px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg transition hover:bg-indigo-700">
              + Add Task
            </button>
          </Link>
        </div>

        {loading ? (
          <div className="grid gap-4 grid-cols-[repeat(auto-fit,minmax(280px,1fr))]">
            {[...Array(3)].map((_, i) => (
              <div
                key={i}
                className="h-32 bg-white rounded-2xl shadow-sm border border-gray-100 animate-pulse"
              />
            ))}
          </div>
        ) : error ? (
          <p className="bg-red-50 text-red-600 text-sm border border-red-200 rounded-lg px-4 py-3">
            {error}
          </p>
        ) : tasks.length === 0 ? (
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 px-8 py-16 text-center">
            <p className="text-gray-500 mb-4">No tasks yet</p>
            <Link
              to="/create"
              className="inline-block px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg transition hover:bg-indigo-700"
            >
              Create your first task
            </Link>
          </div>
        ) : (
          <div className="grid gap-4 grid-cols-[repeat(auto-fit,minmax(280px,1fr))]">
            {tasks.map((task) => (
              <TaskCard key={task.id} task={task} onDelete={handleDelete} />
            ))}
          </div>
        )}
      </div>
    </div>
  );
}