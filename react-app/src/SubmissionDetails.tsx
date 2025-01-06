import { useEffect, useState } from "react";
import { CalendarDaysIcon, UserCircleIcon } from "@heroicons/react/20/solid";
import { useParams } from "react-router-dom";
import { Submission } from "./types";

const SubmissionDetails = () => {
  const { id } = useParams();
  const [submission, setSubmission] = useState<Submission | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchSubmission = async () => {
      const response = await fetch(
        `${window.cf7ReactPlugin.apiUrl}/wp-json/cf7/v1/submissions/${id}`
      );
      const data = await response.json();
      setSubmission(data);
      setLoading(false);
    };

    fetchSubmission();
  }, [id]);

  if (loading) return <p>Loading submission details...</p>;
  if (!submission) return <p>Submission not found.</p>;

  console.log("submission", submission);
  return (
    <div className="container mx-auto">
      <div className="bg-white shadow px-4 py-6 rounded-lg mb-3">
        <div className="px-4 sm:px-0">
          <h3 className="text-base/7 font-semibold text-gray-900">
            Form Submission Details
          </h3>
          <p className="mt-1 max-w-2xl text-sm/6 text-gray-500">
            Details of the form submission.
          </p>
        </div>
      </div>
      <div className="grid grid-cols-1 items-start gap-4 lg:grid-cols-3 lg:gap-8">
        <div className="grid grid-cols-1 gap-4 lg:col-span-2 bg-white shadow px-4 py-6 rounded-lg">
          <div className="min-w-full overflow-x-auto">
            <div className="overflow-hidden rounded-lg">
              <dl className="divide-y divide-gray-100">
                {submission?.submission_data &&
                  Object.entries(submission.submission_data).map(
                    ([key, value]) => (
                      <div className="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                        <dt className="text-sm/6 font-medium text-gray-900">
                          {key}
                        </dt>
                        <dd className="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0">
                          {Array.isArray(value) ? value.join(", ") : value}
                        </dd>
                      </div>
                    )
                  )}
              </dl>
            </div>
          </div>
        </div>
        <div className="grid grid-cols-1 gap-4">
          <section aria-labelledby="section-2-title">
            <div className="overflow-hidden rounded-lg bg-white shadow">
              <h2
                id="section-2-title"
                className="text-base/7 font-semibold text-gray-900 px-3 py-2"
              >
                {submission.form_name}
              </h2>
              <div className="p-6">
                {submission.user_name && (
                  <div className="mt-6 flex w-full flex-none gap-x-4 border-t border-gray-900/5 px-6 pt-6">
                    <dt className="flex-none">
                      <span className="sr-only">Client</span>
                      <UserCircleIcon
                        aria-hidden="true"
                        className="h-6 w-5 text-gray-400"
                      />
                    </dt>
                    <dd className="text-sm/6 font-medium text-gray-900">
                      {submission.user_name}
                    </dd>
                  </div>
                )}
                <div className="mt-4 flex w-full flex-none gap-x-4 px-6">
                  <dt className="flex-none">
                    <span className="sr-only">Due date</span>
                    <CalendarDaysIcon
                      aria-hidden="true"
                      className="h-6 w-5 text-gray-400"
                    />
                  </dt>
                  <dd className="text-sm/6 text-gray-500">
                    <time dateTime={submission.submitted_at}>
                      {submission.submitted_at}
                    </time>
                  </dd>
                </div>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>
  );
};

export default SubmissionDetails;
