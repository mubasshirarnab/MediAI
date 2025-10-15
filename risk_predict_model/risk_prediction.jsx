const { useState } = React;

function RiskPredictionForm() {
  const [formState, setFormState] = useState({
    sex: 'female',
    age: '',
    current_smoker: 'no',
    cigs_per_day: '0',
    cholesterol: '',
    systolic_bp: '',
    diastolic_bp: '',
    bmi: '',
    heart_rate: '',
    glucose: '',
    bp_medications: 'no',
    prevalent_stroke: 'no',
    prevalent_hyp: 'no',
    diabetes: 'no',
  });

  const handleChange = (e) => {
    const { name, value, type } = e.target;
    setFormState((prev) => ({ ...prev, [name]: value }));
  };

  const [predictionResult, setPredictionResult] = useState(null);

  const handlePredictClick = async () => {
    try {
      const response = await fetch('http://localhost:5000/predict', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          age: formState.age,
          sex: formState.sex,
          current_smoker: formState.current_smoker,
          cigs_per_day: formState.cigs_per_day,
          bp_medications: formState.bp_medications,
          prevalent_stroke: formState.prevalent_stroke,
          prevalent_hyp: formState.prevalent_hyp,
          diabetes: formState.diabetes,
          cholesterol: formState.cholesterol,
          systolic_bp: formState.systolic_bp,
          heart_rate: formState.heart_rate,
          glucose: formState.glucose,
        }),
      });
      const result = await response.json();
      if (result.error) throw new Error(result.error);
      setPredictionResult(result);
    } catch (err) {
      alert('Error: ' + err.message);
    }
  };

  return (
    <>
      <iframe
        src="../navbar.php"
        frameBorder="0"
        style={{ width: '100%', height: '80px' }}
      />

      <div className="risk-prediction-container">
        <header className="rp-header">
          <h1>RISK PREDICTION</h1>
        </header>

        <form id="riskPredictionForm" className="rp-form" method="POST" action="">
          <div className="rp-grid">
            {/* Left Column: Demographic, Behavioral, Medical (Current) */}
            <div className="rp-column rp-column-left">
              <section className="rp-section demographic">
                <h2 style={{ color: '#7B55ED', fontWeight: 'bold' }}>Demographic</h2>
                <div className="rp-input-group horizontal">
                  <div className="rp-input-item">
                    <label htmlFor="sex">Sex</label>
                    <select id="sex" name="sex" className="rp-short-input rp-dropdown" value={formState.sex} onChange={handleChange}>
                      <option value="male">Male</option>
                      <option value="female">Female</option>
                    </select>
                  </div>
                  <div className="rp-input-item">
                    <label htmlFor="age">Age</label>
                    <input type="text" id="age" name="age" className="rp-short-input" value={formState.age} onChange={handleChange} />
                  </div>
                </div>
              </section>

              <section className="rp-section behavioral">
                <h2 style={{ color: '#7B55ED', fontWeight: 'bold' }}>Behavioral</h2>
                <div className="rp-input-group vertical">
                  <label>Current Smoker</label>
                  <span className="rp-sublabel">(Whether or not the patient is a current smoker)</span>
                  <div className="rp-radio-group">
                    <input type="radio" id="smoker_yes" name="current_smoker" value="yes" checked={formState.current_smoker === 'yes'} onChange={handleChange} />
                    <label htmlFor="smoker_yes">Yes</label>
                    <input type="radio" id="smoker_no" name="current_smoker" value="no" checked={formState.current_smoker === 'no'} onChange={handleChange} />
                    <label htmlFor="smoker_no">No</label>
                  </div>
                </div>
                <div className="rp-input-group vertical">
                  <label htmlFor="cigs_per_day">Cigarettes Per Day</label>
                  <span className="rp-sublabel">(The number of cigarettes that the person smoked on average in one day. <br/>[can be considered continuous as one can have any number of cigarettes, even half a cigarette])</span>
                  <input type="text" id="cigs_per_day" name="cigs_per_day" className="rp-value-input" style={{ width: '100px' }} value={formState.cigs_per_day} onChange={handleChange} />
                </div>
              </section>

              <section className="rp-section medical-current">
                <h2 style={{ color: '#7B55ED', fontWeight: 'bold' }}>Medical (current)</h2>
                <div className="rp-input-row">
                  <label htmlFor="cholesterol">Cholesterol Level</label>
                  <input type="text" id="cholesterol" name="cholesterol" className="rp-value-input" value={formState.cholesterol} onChange={handleChange} />
                </div>
                <div className="rp-input-row">
                  <label htmlFor="systolic_bp">Systolic Blood Pressure</label>
                  <input type="text" id="systolic_bp" name="systolic_bp" className="rp-value-input" value={formState.systolic_bp} onChange={handleChange} />
                </div>
                <div className="rp-input-row">
                  <label htmlFor="diastolic_bp">Diastolic Blood Pressure</label>
                  <input type="text" id="diastolic_bp" name="diastolic_bp" className="rp-value-input" value={formState.diastolic_bp} onChange={handleChange} />
                </div>
                <div className="rp-input-row">
                  <label htmlFor="bmi">Body Mass Index</label>
                  <input type="text" id="bmi" name="bmi" className="rp-value-input" value={formState.bmi} onChange={handleChange} />
                </div>
                <div className="rp-input-row">
                  <label htmlFor="heart_rate">Heart Rate</label>
                  <input type="text" id="heart_rate" name="heart_rate" className="rp-value-input" value={formState.heart_rate} onChange={handleChange} />
                </div>
                <div className="rp-input-row">
                  <label htmlFor="glucose">Glucose level</label>
                  <input type="text" id="glucose" name="glucose" className="rp-value-input" value={formState.glucose} onChange={handleChange} />
                </div>
              </section>
            </div>

            {/* Right Column: Medical History */}
            <div className="rp-column rp-column-right" style={{ marginLeft: '30px' }}>
              <section className="rp-section medical-history">
                <h2 style={{ color: '#7B55ED', fontWeight: 'bold' }}>Medical History</h2>
                <div className="rp-input-group vertical">
                  <label>BP Medications</label>
                  <span className="rp-sublabel">(Whether or not the patient was on blood pressure medication [Nominal])</span>
                  <div className="rp-radio-group">
                    <input type="radio" id="bp_med_yes" name="bp_medications" value="yes" checked={formState.bp_medications === 'yes'} onChange={handleChange} />
                    <label htmlFor="bp_med_yes">Yes</label>
                    <input type="radio" id="bp_med_no" name="bp_medications" value="no" checked={formState.bp_medications === 'no'} onChange={handleChange} />
                    <label htmlFor="bp_med_no">No</label>
                  </div>
                </div>
                <div className="rp-input-group vertical">
                  <label>Prevalent Stroke</label>
                  <span className="rp-sublabel">(Whether or not the patient had previously had a stroke [Nominal])</span>
                  <div className="rp-radio-group">
                    <input type="radio" id="stroke_yes" name="prevalent_stroke" value="yes" checked={formState.prevalent_stroke === 'yes'} onChange={handleChange} />
                    <label htmlFor="stroke_yes">Yes</label>
                    <input type="radio" id="stroke_no" name="prevalent_stroke" value="no" checked={formState.prevalent_stroke === 'no'} onChange={handleChange} />
                    <label htmlFor="stroke_no">No</label>
                  </div>
                </div>
                <div className="rp-input-group vertical">
                  <label>Prevalent Hyp</label>
                  <span className="rp-sublabel">(Whether or not the patient was hypertensive [Nominal])</span>
                  <div className="rp-radio-group">
                    <input type="radio" id="hyp_yes" name="prevalent_hyp" value="yes" checked={formState.prevalent_hyp === 'yes'} onChange={handleChange} />
                    <label htmlFor="hyp_yes">Yes</label>
                    <input type="radio" id="hyp_no" name="prevalent_hyp" value="no" checked={formState.prevalent_hyp === 'no'} onChange={handleChange} />
                    <label htmlFor="hyp_no">No</label>
                  </div>
                </div>
                <div className="rp-input-group vertical">
                  <label>Diabetes</label>
                  <span className="rp-sublabel">(Whether or not the patient had diabetes [Nominal])</span>
                  <div className="rp-radio-group">
                    <input type="radio" id="diabetes_yes" name="diabetes" value="yes" checked={formState.diabetes === 'yes'} onChange={handleChange} />
                    <label htmlFor="diabetes_yes">Yes</label>
                    <input type="radio" id="diabetes_no" name="diabetes" value="no" checked={formState.diabetes === 'no'} onChange={handleChange} />
                    <label htmlFor="diabetes_no">No</label>
                  </div>
                </div>
              </section>
            </div>
          </div>

          <div className="rp-predict-button-container">
            <button type="button" className="rp-predict-btn" onClick={handlePredictClick}>Predict</button>
          </div>
          {predictionResult && (
            <div
              className="prediction-result"
              style={{
                marginTop: '20px',
                padding: '20px',
                borderRadius: '10px',
                backgroundColor: predictionResult.prediction === 'High Risk' ? '#ffe6e6' : '#e6ffe6',
                border: `2px solid ${predictionResult.prediction === 'High Risk' ? '#ff9999' : '#99ff99'}`,
              }}
            >
              <h3
                style={{
                  color: predictionResult.prediction === 'High Risk' ? '#cc0000' : '#006600',
                  marginBottom: '10px',
                }}
              >
                {predictionResult.prediction}
              </h3>
              <p style={{ margin: '5px 0', color: 'black' }}>
                Risk Probability: {predictionResult.risk_percentage}
              </p>
              <p style={{ margin: '5px 0', fontSize: '0.9em', color: 'black' }}>
                {predictionResult.prediction === 'High Risk'
                  ? 'Please consult with a healthcare professional for a thorough evaluation.'
                  : 'Continue maintaining a healthy lifestyle!'}
              </p>
            </div>
          )}
        </form>
      </div>
    </>
  );
}

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(<RiskPredictionForm />);


