import { HashRouter as Router, Routes, Route } from "react-router-dom";

// Import views
import SubmissionFormList from "./SubmissionFormList";
import SubmissionsList from "./SubmissionsList";
import SubmissionDetails from "./SubmissionDetails";
import SubmissionByForm from "./SubmissionByForm";

const App = () => {
  return (
    <Router>
      <Routes>
        {/* Default route */}
        <Route path="/" element={<SubmissionFormList />} />
        <Route path="/all-submissions" element={<SubmissionsList />} />
        <Route
          path="/form/:form_id/submission"
          element={<SubmissionByForm />}
        />
        <Route
          path="/form/:form_id/submission/:id"
          element={<SubmissionDetails />}
        />
      </Routes>
    </Router>
  );
};

export default App;
