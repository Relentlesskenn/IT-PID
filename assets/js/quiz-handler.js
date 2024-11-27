class QuizHandler {
    constructor(container, quizData) {
        this.container = container;
        this.quizData = quizData;
        this.currentQuestion = 0;
        this.score = 0;
        this.selectedAnswer = null;
        this.quizCompleted = false;
        this.init();
    }

    init() {
        this.render();
        this.attachEventListeners();
    }

    render() {
        if (!this.quizData || this.quizData.length === 0) {
            return;
        }

        if (this.quizCompleted) {
            this.renderCompletion();
        } else {
            this.renderQuestion();
        }
    }

    renderQuestion() {
        const question = this.quizData[this.currentQuestion];
        const html = `
            <div class="quiz-container">
                <h3 class="quiz-title">Test Your Knowledge</h3>
                <div class="quiz-progress">
                    Question ${this.currentQuestion + 1} of ${this.quizData.length}
                </div>
                <div class="quiz-question">
                    ${question.question}
                </div>
                <div class="quiz-answers">
                    ${question.answers.map((answer, index) => `
                        <button class="quiz-answer-btn" data-index="${index}">
                            ${answer}
                        </button>
                    `).join('')}
                </div>
                <div class="quiz-feedback" style="display: none;"></div>
                <div class="quiz-actions">
                    <button class="quiz-check-btn" style="display: none;">
                        Check Answer
                    </button>
                    <button class="quiz-next-btn" style="display: none;">
                        ${this.currentQuestion + 1 === this.quizData.length ? 'Finish Quiz' : 'Next Question'}
                    </button>
                </div>
            </div>
        `;

        this.container.innerHTML = html;
    }

    renderCompletion() {
        const html = `
            <div class="quiz-container">
                <h3 class="quiz-title">Quiz Completed!</h3>
                <div class="quiz-completion">
                    <p>You scored ${this.score} out of ${this.quizData.length} questions correctly.</p>
                    <button class="quiz-retry-btn">Try Again</button>
                </div>
            </div>
        `;

        this.container.innerHTML = html;
    }

    attachEventListeners() {
        this.container.addEventListener('click', (e) => {
            if (e.target.matches('.quiz-answer-btn')) {
                this.handleAnswerSelect(e.target);
            } else if (e.target.matches('.quiz-check-btn')) {
                this.handleCheckAnswer();
            } else if (e.target.matches('.quiz-next-btn')) {
                this.handleNextQuestion();
            } else if (e.target.matches('.quiz-retry-btn')) {
                this.handleRetry();
            }
        });
    }

    handleAnswerSelect(button) {
        if (this.quizCompleted) return;

        // Remove active class from all answers
        this.container.querySelectorAll('.quiz-answer-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Add active class to selected answer
        button.classList.add('active');
        this.selectedAnswer = parseInt(button.dataset.index);

        // Show check answer button
        this.container.querySelector('.quiz-check-btn').style.display = 'block';
    }

    handleCheckAnswer() {
        const question = this.quizData[this.currentQuestion];
        const feedbackDiv = this.container.querySelector('.quiz-feedback');
        const isCorrect = this.selectedAnswer === question.correctAnswer;

        // Show feedback
        feedbackDiv.innerHTML = `
            <div class="quiz-feedback-${isCorrect ? 'correct' : 'incorrect'}">
                ${isCorrect ? 'Correct! ' : 'Incorrect. '}
                ${question.explanation}
            </div>
        `;
        feedbackDiv.style.display = 'block';

        // Update button visibility
        this.container.querySelector('.quiz-check-btn').style.display = 'none';
        this.container.querySelector('.quiz-next-btn').style.display = 'block';

        // Highlight correct and incorrect answers
        const answerButtons = this.container.querySelectorAll('.quiz-answer-btn');
        answerButtons.forEach((btn, index) => {
            if (index === question.correctAnswer) {
                btn.classList.add('correct');
            } else if (index === this.selectedAnswer && !isCorrect) {
                btn.classList.add('incorrect');
            }
        });
    }

    handleNextQuestion() {
        if (this.selectedAnswer === this.quizData[this.currentQuestion].correctAnswer) {
            this.score++;
        }

        if (this.currentQuestion + 1 < this.quizData.length) {
            this.currentQuestion++;
            this.selectedAnswer = null;
            this.render();
        } else {
            this.quizCompleted = true;
            this.render();
        }
    }

    handleRetry() {
        this.currentQuestion = 0;
        this.score = 0;
        this.selectedAnswer = null;
        this.quizCompleted = false;
        this.render();
    }
}