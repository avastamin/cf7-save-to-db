//SubmissionByForm.tsx
declare global {
  interface Window {
    cf7ReactPlugin: {
      apiUrl: string;
    };
  }
}

import { useState, useEffect } from "react";
import { truncateString } from "./helpers";
import { Submission } from "./types";
import { Link, useParams } from "react-router-dom";

const SubmissionByForm = () => {
  const { form_id } = useParams();
  const [submissions, setSubmissions] = useState<Submission[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchSubmissions = async () => {
      try {
        const response = await fetch(
          `${window.cf7ReactPlugin.apiUrl}/wp-json/cf7/v1/submissions/form/${form_id}`
        );
        const data = await response.json();
        console.log("submissions", data);
        setSubmissions(data);
        setLoading(false);
      } catch (error) {
        console.error("Error fetching submissions:", error);
        setLoading(false);
      }
    };

    fetchSubmissions();
  }, [form_id]);

  console.log("SubmissionByForm", submissions);
  if (loading) {
    return <div>Loading submissions...</div>;
  }

  // Get unique keys from submission_data for table headers
  const submissionDataKeys =
    submissions.length > 0
      ? Object.keys(submissions[0].submission_data || {})
      : [];

  return (
    <div className="container mx-auto bg-white shadow rounded-lg mt-8">
      <h1 className="text-2xl font-bold px-3 py-3">Single Form Submissions</h1>
      <div className="mt-8 flow-root">
        <div className="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div className="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <table className="min-w-full divide-y divide-gray-300">
              <thead>
                <tr>
                  <th
                    scope="col"
                    className="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-3"
                  >
                    ID
                  </th>
                  <th
                    scope="col"
                    className="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-3"
                  >
                    Form ID
                  </th>
                  <th
                    scope="col"
                    className="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-3"
                  >
                    Form Name
                  </th>
                  {submissionDataKeys.map((key) => (
                    <th
                      key={key}
                      className="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-3"
                    >
                      {key}
                    </th>
                  ))}
                  <th
                    scope="col"
                    className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
                  >
                    Submitted At
                  </th>
                  <th scope="col" className="relative py-3.5 pl-3 pr-4 sm:pr-3">
                    <span className="sr-only">View</span>
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white">
                {submissions.map((submission) => (
                  <tr
                    key={submission.id}
                    className="even:bg-gray-50 cursor-pointer"
                  >
                    <td className="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-3">
                      {submission.id}
                    </td>
                    <td className="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-3">
                      {submission.form_id}
                    </td>
                    <td className="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-3">
                      {submission?.form_name}
                    </td>
                    {submissionDataKeys.map((key) => (
                      <td
                        key={`${submission.id}-${key}`}
                        className="whitespace-nowrap px-3 py-4 text-sm text-gray-500"
                      >
                        {typeof submission.submission_data[key] === "string"
                          ? truncateString(submission.submission_data[key], 60)
                          : JSON.stringify(submission.submission_data[key])}
                      </td>
                    ))}
                    <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      {submission.submitted_at}
                    </td>
                    <td className="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-3">
                      <a
                        href="#"
                        className="text-indigo-600 hover:text-indigo-900 hidden"
                      >
                        View<span className="sr-only">, {submission.id}</span>
                      </a>
                      <Link
                        to={`/form/${submission.form_id}/submission/${submission.id}`}
                      >
                        View Details
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SubmissionByForm;
