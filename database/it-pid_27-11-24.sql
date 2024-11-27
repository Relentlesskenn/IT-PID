-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 27, 2024 at 12:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `it-pid`
--
CREATE DATABASE IF NOT EXISTS `it-pid` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `it-pid`;

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `preview` text NOT NULL,
  `content` text NOT NULL,
  `date_published` date DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'published',
  `quiz_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`quiz_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`id`, `title`, `category`, `preview`, `content`, `date_published`, `created_at`, `status`, `quiz_data`) VALUES
(1, 'Understanding Basic Budgeting Principles', 'basics', 'Master the fundamental concepts of budgeting and take control of your financial future with these essential principles.', '<h4>Understanding Basic Budgeting Principles</h4>\r\n    <p>A solid budget is the foundation of financial success. Here are the key principles you need to know:</p>\r\n    <h5>1. Income Tracking</h5>\r\n    <p>Track all sources of income, including:</p>\r\n    <ul>\r\n        <li>Regular salary</li>\r\n        <li>Side hustles</li>\r\n        <li>Investments</li>\r\n        <li>Other income sources</li>\r\n    </ul>\r\n    <h5>2. Expense Categorization</h5>\r\n    <p>Organize your expenses into categories:</p>\r\n    <ul>\r\n        <li>Fixed expenses (rent, utilities)</li>\r\n        <li>Variable expenses (groceries, entertainment)</li>\r\n        <li>Irregular expenses (car maintenance, gifts)</li>\r\n    </ul>\r\n    <h5>3. Setting Financial Goals</h5>\r\n    <p>Create SMART financial goals that are:</p>\r\n    <ul>\r\n        <li>Specific</li>\r\n        <li>Measurable</li>\r\n        <li>Achievable</li>\r\n        <li>Relevant</li>\r\n        <li>Time-bound</li>\r\n    </ul>\r\n    <p>Remember: A budget is a living document that should be reviewed and adjusted regularly.</p>', '2024-10-25', '2024-10-24 18:16:49', 'published', '[\r\n    {\r\n        \"question\": \"What are the key components that should be tracked in income?\",\r\n        \"answers\": [\r\n            \"Only regular salary\",\r\n            \"Regular salary and investments\",\r\n            \"Regular salary, side hustles, investments, and other income sources\",\r\n            \"Only side hustles\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"All income sources should be tracked including regular salary, side hustles, investments, and other sources.\"\r\n    },\r\n    {\r\n        \"question\": \"Which of these is NOT a fixed expense?\",\r\n        \"answers\": [\r\n            \"Rent\",\r\n            \"Groceries\",\r\n            \"Insurance premiums\",\r\n            \"Car payments\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Groceries is a variable expense as the amount can change monthly, while rent, insurance, and car payments are typically fixed.\"\r\n    },\r\n    {\r\n        \"question\": \"What does the M in SMART goals stand for?\",\r\n        \"answers\": [\r\n            \"Manageable\",\r\n            \"Measurable\",\r\n            \"Meaningful\",\r\n            \"Monthly\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"In SMART goals, M stands for Measurable, meaning you can track and quantify your progress.\"\r\n    },\r\n    {\r\n        \"question\": \"How often should a budget be reviewed and adjusted?\",\r\n        \"answers\": [\r\n            \"Once a year\",\r\n            \"Never, once set it\'s final\",\r\n            \"Regularly\",\r\n            \"Only when income changes\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"A budget is a living document that should be reviewed and adjusted regularly to stay effective.\"\r\n    },\r\n    {\r\n        \"question\": \"What is considered an irregular expense?\",\r\n        \"answers\": [\r\n            \"Monthly rent\",\r\n            \"Car maintenance\",\r\n            \"Weekly groceries\",\r\n            \"Utility bills\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Car maintenance is an irregular expense as it doesn\'t occur on a fixed schedule and can vary in amount.\"\r\n    }\r\n]'),
(2, 'Creating Your First Budget', 'basics', 'Learn how to create and maintain your first budget with this step-by-step guide designed for beginners.', '<h4>Creating Your First Budget</h4>\r\n    <p>Follow these steps to create your first budget:</p>\r\n    <ol>\r\n        <li><strong>Gather Your Financial Information</strong>\r\n            <ul>\r\n                <li>Bank statements</li>\r\n                <li>Pay stubs</li>\r\n                <li>Bills and receipts</li>\r\n            </ul>\r\n        </li>\r\n        <li><strong>Calculate Your Total Income</strong>\r\n            <ul>\r\n                <li>Regular salary</li>\r\n                <li>Additional income</li>\r\n            </ul>\r\n        </li>\r\n        <li><strong>List All Expenses</strong>\r\n            <ul>\r\n                <li>Fixed expenses</li>\r\n                <li>Variable expenses</li>\r\n                <li>Discretionary spending</li>\r\n            </ul>\r\n        </li>\r\n        <li><strong>Set Realistic Goals</strong></li>\r\n        <li><strong>Monitor and Adjust</strong></li>\r\n    </ol>', '2024-10-25', '2024-10-24 18:16:49', 'published', '[\r\n    {\r\n        \"question\": \"What is the first step in creating a budget?\",\r\n        \"answers\": [\r\n            \"Set spending limits\",\r\n            \"Gather financial information\",\r\n            \"Make savings goals\",\r\n            \"Cut expenses\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"The first step is gathering all your financial information including bank statements, pay stubs, and bills.\"\r\n    },\r\n    {\r\n        \"question\": \"Which documents are NOT typically needed for initial budget creation?\",\r\n        \"answers\": [\r\n            \"Bank statements\",\r\n            \"Pay stubs\",\r\n            \"Social media accounts\",\r\n            \"Bills and receipts\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Social media accounts are not relevant for budget creation, unlike financial documents like statements, pay stubs, and bills.\"\r\n    },\r\n    {\r\n        \"question\": \"What should you include in your total income calculation?\",\r\n        \"answers\": [\r\n            \"Only your main salary\",\r\n            \"Salary and bonuses\",\r\n            \"Regular salary and additional income\",\r\n            \"Whatever you remember spending\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Total income should include both regular salary and any additional income sources for accurate budgeting.\"\r\n    },\r\n    {\r\n        \"question\": \"What is discretionary spending?\",\r\n        \"answers\": [\r\n            \"Essential expenses\",\r\n            \"Bills and utilities\",\r\n            \"Non-essential or optional expenses\",\r\n            \"Emergency funds\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Discretionary spending refers to non-essential or optional expenses that aren\'t required for basic living.\"\r\n    },\r\n    {\r\n        \"question\": \"Why is monitoring and adjusting your budget important?\",\r\n        \"answers\": [\r\n            \"To make it more complicated\",\r\n            \"To keep it unchanged\",\r\n            \"To adapt to changing circumstances\",\r\n            \"To impress others\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Monitoring and adjusting your budget is crucial to adapt to changing circumstances and maintain its effectiveness.\"\r\n    }\r\n]'),
(3, 'The 50/30/20 Rule Explained', 'basics', 'Discover how to apply the popular 50/30/20 budgeting rule to effectively manage your monthly income.', '<h4>The 50/30/20 Rule Explained</h4>\r\n    <p>The 50/30/20 rule is a simple but effective budgeting method that divides your income into three main categories:</p>\r\n    <h5>50% - Needs</h5>\r\n    <ul>\r\n        <li>Rent/Mortgage</li>\r\n        <li>Utilities</li>\r\n        <li>Groceries</li>\r\n        <li>Basic transportation</li>\r\n        <li>Insurance</li>\r\n    </ul>\r\n    <h5>30% - Wants</h5>\r\n    <ul>\r\n        <li>Entertainment</li>\r\n        <li>Dining out</li>\r\n        <li>Shopping</li>\r\n        <li>Hobbies</li>\r\n        <li>Subscriptions</li>\r\n    </ul>\r\n    <h5>20% - Savings & Debt</h5>\r\n    <ul>\r\n        <li>Emergency fund</li>\r\n        <li>Retirement savings</li>\r\n        <li>Debt repayment</li>\r\n        <li>Investments</li>\r\n    </ul>', '2024-10-25', '2024-10-24 18:16:49', 'published', '[\r\n    {\r\n        \"question\": \"What percentage is allocated to needs in the 50/30/20 rule?\",\r\n        \"answers\": [\r\n            \"20%\",\r\n            \"30%\",\r\n            \"40%\",\r\n            \"50%\"\r\n        ],\r\n        \"correctAnswer\": 3,\r\n        \"explanation\": \"The 50/30/20 rule allocates 50% of your income to needs (essential expenses).\"\r\n    },\r\n    {\r\n        \"question\": \"Which category would entertainment fall under?\",\r\n        \"answers\": [\r\n            \"Needs\",\r\n            \"Wants\",\r\n            \"Savings\",\r\n            \"Investments\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Entertainment is considered a want, falling under the 30% wants category.\"\r\n    },\r\n    {\r\n        \"question\": \"What percentage is recommended for savings and debt repayment?\",\r\n        \"answers\": [\r\n            \"50%\",\r\n            \"30%\",\r\n            \"20%\",\r\n            \"10%\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"The rule allocates 20% for savings and debt repayment.\"\r\n    },\r\n    {\r\n        \"question\": \"Which of these is considered a \'need\'?\",\r\n        \"answers\": [\r\n            \"Movie tickets\",\r\n            \"Groceries\",\r\n            \"Gym membership\",\r\n            \"Streaming services\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Groceries are considered a basic need as they are essential for survival.\"\r\n    },\r\n    {\r\n        \"question\": \"What should be included in the 20% category?\",\r\n        \"answers\": [\r\n            \"Dining out\",\r\n            \"Utilities\",\r\n            \"Emergency fund savings\",\r\n            \"Cable TV\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Emergency fund savings fall under the 20% category for savings and debt repayment.\"\r\n    }\r\n]'),
(4, 'Managing Fixed vs Variable Expenses', 'basics', 'Learn the difference between fixed and variable expenses and how to effectively budget for both types of costs.', '<h4>Managing Fixed vs Variable Expenses</h4>\r\n    <p>Understanding the difference between fixed and variable expenses is crucial for effective budgeting.</p>\r\n    <h5>Fixed Expenses</h5>\r\n    <ul>\r\n        <li>Rent/Mortgage payments</li>\r\n        <li>Car payments</li>\r\n        <li>Insurance premiums</li>\r\n        <li>Phone bills</li>\r\n    </ul>\r\n    <h5>Variable Expenses</h5>\r\n    <ul>\r\n        <li>Groceries</li>\r\n        <li>Utilities</li>\r\n        <li>Entertainment</li>\r\n        <li>Transportation costs</li>\r\n    </ul>\r\n    <h5>Tips for Management</h5>\r\n    <ol>\r\n        <li>Track all expenses for at least a month</li>\r\n        <li>Find areas where you can reduce variable costs</li>\r\n        <li>Look for ways to lower fixed expenses</li>\r\n        <li>Build a buffer in your budget for variable expenses</li>\r\n    </ol>', '2024-10-25', '2024-10-24 18:16:49', 'published', '[\r\n    {\r\n        \"question\": \"Which of these is a fixed expense?\",\r\n        \"answers\": [\r\n            \"Groceries\",\r\n            \"Entertainment\",\r\n            \"Mortgage payment\",\r\n            \"Gas for car\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"A mortgage payment is a fixed expense because it remains the same amount each month.\"\r\n    },\r\n    {\r\n        \"question\": \"Why is tracking expenses important?\",\r\n        \"answers\": [\r\n            \"To spend more money\",\r\n            \"To understand spending patterns\",\r\n            \"To increase variable costs\",\r\n            \"To avoid budgeting\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Tracking expenses helps understand spending patterns and identify areas for improvement.\"\r\n    },\r\n    {\r\n        \"question\": \"What makes an expense variable?\",\r\n        \"answers\": [\r\n            \"It\'s paid annually\",\r\n            \"It stays the same each month\",\r\n            \"The amount changes regularly\",\r\n            \"It\'s automatically deducted\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Variable expenses are those that change in amount from month to month.\"\r\n    },\r\n    {\r\n        \"question\": \"How should you handle variable expenses in a budget?\",\r\n        \"answers\": [\r\n            \"Ignore them\",\r\n            \"Build in a buffer\",\r\n            \"Only track fixed expenses\",\r\n            \"Treat them as fixed\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Building a buffer in your budget helps manage the fluctuation in variable expenses.\"\r\n    },\r\n    {\r\n        \"question\": \"Which is NOT a way to reduce variable costs?\",\r\n        \"answers\": [\r\n            \"Using energy-efficient appliances\",\r\n            \"Shopping with a list\",\r\n            \"Paying a fixed mortgage\",\r\n            \"Reducing water usage\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Paying a fixed mortgage is not a way to reduce variable costs as it\'s already a fixed expense.\"\r\n    }\r\n]'),
(5, 'Building Your Emergency Fund', 'savings', 'Learn why and how to build an emergency fund that can protect you from unexpected financial challenges.', '<h4>Building Your Emergency Fund</h4>\r\n    <p>An emergency fund is your financial safety net. Here\'s how to build one:</p>\r\n    <h5>Why You Need an Emergency Fund</h5>\r\n    <ul>\r\n        <li>Protection against job loss</li>\r\n        <li>Coverage for medical emergencies</li>\r\n        <li>Handle unexpected repairs</li>\r\n        <li>Avoid debt for emergencies</li>\r\n    </ul>\r\n    <h5>How Much to Save</h5>\r\n    <ul>\r\n        <li>Start with ₱5,000</li>\r\n        <li>Build to one month of expenses</li>\r\n        <li>Aim for 3-6 months of expenses</li>\r\n        <li>Consider your job stability</li>\r\n    </ul>\r\n    <h5>Saving Strategies</h5>\r\n    <ol>\r\n        <li>Set up automatic transfers</li>\r\n        <li>Save your windfalls</li>\r\n        <li>Cut unnecessary expenses</li>\r\n        <li>Keep it in a separate account</li>\r\n    </ol>', '2024-10-25', '2024-10-24 18:16:49', 'published', '[\r\n    {\r\n        \"question\": \"What is the recommended size for an emergency fund?\",\r\n        \"answers\": [\r\n            \"1 week of expenses\",\r\n            \"2 weeks of expenses\",\r\n            \"3-6 months of expenses\",\r\n            \"1 year of expenses\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Financial experts recommend having 3-6 months of expenses saved in an emergency fund.\"\r\n    },\r\n    {\r\n        \"question\": \"Why is ₱5,000 suggested as a starting point?\",\r\n        \"answers\": [\r\n            \"It\'s the maximum needed\",\r\n            \"It\'s an achievable first milestone\",\r\n            \"It\'s enough for all emergencies\",\r\n            \"It\'s required by banks\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"₱5,000 is suggested as an achievable first milestone to build momentum in saving.\"\r\n    },\r\n    {\r\n        \"question\": \"What should NOT be considered when determining fund size?\",\r\n        \"answers\": [\r\n            \"Job stability\",\r\n            \"Monthly expenses\",\r\n            \"Investment returns\",\r\n            \"Insurance coverage\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Investment returns should not be a primary factor in determining emergency fund size, as the fund should be readily accessible.\"\r\n    },\r\n    {\r\n        \"question\": \"Which is the best way to build an emergency fund?\",\r\n        \"answers\": [\r\n            \"Using credit cards\",\r\n            \"Taking out loans\",\r\n            \"Automatic transfers\",\r\n            \"Waiting for bonuses\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Setting up automatic transfers ensures consistent saving and helps build the emergency fund steadily.\"\r\n    },\r\n    {\r\n        \"question\": \"Where should you keep your emergency fund?\",\r\n        \"answers\": [\r\n            \"Invested in stocks\",\r\n            \"In a separate savings account\",\r\n            \"In cryptocurrency\",\r\n            \"Under your mattress\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Emergency funds should be kept in a separate, easily accessible savings account.\"\r\n    }\r\n]'),
(6, 'Smart Money-Saving Tips', 'savings', 'Discover practical tips and tricks to save money in your daily life without sacrificing your quality of life.', '<h4>Smart Money-Saving Tips</h4>\r\n    <p>Here are practical ways to save money every day:</p>\r\n    <h5>Shopping Tips</h5>\r\n    <ul>\r\n        <li>Use price comparison tools</li>\r\n        <li>Buy in bulk when practical</li>\r\n        <li>Shop with a list</li>\r\n        <li>Take advantage of sales</li>\r\n    </ul>\r\n    <h5>Utility Savings</h5>\r\n    <ul>\r\n        <li>Use energy-efficient appliances</li>\r\n        <li>Optimize your electricity usage</li>\r\n        <li>Reduce water consumption</li>\r\n        <li>Monitor your bills</li>\r\n    </ul>\r\n    <h5>Entertainment Savings</h5>\r\n    <ul>\r\n        <li>Look for free activities</li>\r\n        <li>Use student and senior discounts</li>\r\n        <li>Take advantage of happy hours</li>\r\n        <li>Find group deals</li>\r\n    </ul>', '2024-10-25', '2024-10-24 18:16:49', 'published', '[\r\n    {\r\n        \"question\": \"What is the most effective way to save on shopping?\",\r\n        \"answers\": [\r\n            \"Buying everything on sale\",\r\n            \"Using price comparison tools\",\r\n            \"Always buying in bulk\",\r\n            \"Shopping when hungry\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Using price comparison tools helps ensure you\'re getting the best value for your money.\"\r\n    },\r\n    {\r\n        \"question\": \"Which utility saving strategy is most effective?\",\r\n        \"answers\": [\r\n            \"Never using appliances\",\r\n            \"Using energy-efficient appliances\",\r\n            \"Keeping lights on always\",\r\n            \"Ignoring maintenance\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Energy-efficient appliances provide long-term savings while maintaining functionality.\"\r\n    },\r\n    {\r\n        \"question\": \"What\'s the best approach to entertainment savings?\",\r\n        \"answers\": [\r\n            \"Never having fun\",\r\n            \"Looking for free activities\",\r\n            \"Always paying full price\",\r\n            \"Using credit cards\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Looking for free activities allows you to enjoy entertainment while saving money.\"\r\n    },\r\n    {\r\n        \"question\": \"How can you save on groceries?\",\r\n        \"answers\": [\r\n            \"Shopping without a list\",\r\n            \"Buying only brand names\",\r\n            \"Shopping with a list\",\r\n            \"Shopping daily\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Shopping with a list helps avoid impulse purchases and stay within budget.\"\r\n    },\r\n    {\r\n        \"question\": \"Which is NOT a smart saving strategy?\",\r\n        \"answers\": [\r\n            \"Comparing prices\",\r\n            \"Using coupons\",\r\n            \"Impulse buying\",\r\n            \"Bulk buying essentials\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Impulse buying often leads to unnecessary spending and breaks the budget.\"\r\n    }\r\n]'),
(7, 'Automated Savings Strategies', 'savings', 'Learn how to automate your savings to make the process effortless and more effective.', '<h4>Automated Savings Strategies</h4>\r\n    <p>Automation is key to consistent savings. Here\'s how to set it up:</p>\r\n    <h5>Types of Automated Savings</h5>\r\n    <ul>\r\n        <li>Direct deposit splitting</li>\r\n        <li>Automatic transfers</li>\r\n        <li>Round-up programs</li>\r\n        <li>Recurring investments</li>\r\n    </ul>\r\n    <h5>Setting Up Automation</h5>\r\n    <ol>\r\n        <li>Choose your savings goals</li>\r\n        <li>Determine contribution amounts</li>\r\n        <li>Set up automatic transfers</li>\r\n        <li>Monitor and adjust as needed</li>\r\n    </ol>\r\n    <h5>Benefits</h5>\r\n    <ul>\r\n        <li>Consistency in saving</li>\r\n        <li>Reduced temptation to spend</li>\r\n        <li>Better budgeting habits</li>\r\n        <li>Faster goal achievement</li>\r\n    </ul>', '2024-10-25', '2024-10-24 18:16:49', 'published', '[\r\n    {\r\n        \"question\": \"What is the main advantage of automated savings?\",\r\n        \"answers\": [\r\n            \"Higher interest rates\",\r\n            \"Consistency in saving\",\r\n            \"More spending money\",\r\n            \"Lower bank fees\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Automated savings ensure consistency by removing the need for manual transfers.\"\r\n    },\r\n    {\r\n        \"question\": \"What is direct deposit splitting?\",\r\n        \"answers\": [\r\n            \"Sharing money with friends\",\r\n            \"Dividing salary between accounts\",\r\n            \"Splitting bills\",\r\n            \"Sharing investments\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Direct deposit splitting allows you to automatically divide your paycheck between different accounts.\"\r\n    },\r\n    {\r\n        \"question\": \"What are round-up programs?\",\r\n        \"answers\": [\r\n            \"Rounding bills up\",\r\n            \"Saving spare change from purchases\",\r\n            \"Increasing expenses\",\r\n            \"Rounding down savings\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Round-up programs automatically save the difference when rounding up purchases to the nearest peso.\"\r\n    },\r\n    {\r\n        \"question\": \"How often should automated transfers be reviewed?\",\r\n        \"answers\": [\r\n            \"Never\",\r\n            \"Only when problems occur\",\r\n            \"Regularly\",\r\n            \"Once in lifetime\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Regular reviews ensure your automated savings align with your current financial situation.\"\r\n    },\r\n    {\r\n        \"question\": \"What\'s the best time to set up automated savings?\",\r\n        \"answers\": [\r\n            \"After spending money\",\r\n            \"When you\'re in debt\",\r\n            \"Right after receiving income\",\r\n            \"End of the month\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Setting up transfers right after receiving income ensures savings are prioritized.\"\r\n    }\r\n]'),
(8, 'Understanding Different Savings Accounts', 'savings', 'Compare different types of savings accounts and learn which ones best suit your financial goals.', '<h4>Understanding Different Savings Accounts</h4>\r\n    <p>Choose the right savings account for your needs:</p>\r\n    <h5>Regular Savings Account</h5>\r\n    <ul>\r\n        <li>Easy access to funds</li>\r\n        <li>Lower interest rates</li>\r\n        <li>Minimal fees</li>\r\n        <li>Basic features</li>\r\n    </ul>\r\n    <h5>High-Yield Savings Account</h5>\r\n    <ul>\r\n        <li>Better interest rates</li>\r\n        <li>Higher minimum balance</li>\r\n        <li>Online banking features</li>\r\n        <li>Limited withdrawals</li>\r\n    </ul>\r\n    <h5>Time Deposits</h5>\r\n    <ul>\r\n        <li>Highest interest rates</li>\r\n        <li>Fixed terms</li>\r\n        <li>Penalty for early withdrawal</li>\r\n        <li>Better for long-term savings</li>\r\n    </ul>', '2024-10-25', '2024-10-24 18:16:49', 'published', '[\r\n    {\r\n        \"question\": \"What characterizes a regular savings account?\",\r\n        \"answers\": [\r\n            \"High interest rates\",\r\n            \"Limited access\",\r\n            \"Easy access to funds\",\r\n            \"No minimum balance\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Regular savings accounts are characterized by easy access to funds but typically offer lower interest rates.\"\r\n    },\r\n    {\r\n        \"question\": \"What is a key feature of high-yield savings accounts?\",\r\n        \"answers\": [\r\n            \"No minimum balance\",\r\n            \"Higher interest rates\",\r\n            \"Unlimited withdrawals\",\r\n            \"No online features\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"High-yield savings accounts offer better interest rates but often require higher minimum balances.\"\r\n    },\r\n    {\r\n        \"question\": \"Why choose a time deposit?\",\r\n        \"answers\": [\r\n            \"Easy access\",\r\n            \"Low interest rates\",\r\n            \"Highest interest rates\",\r\n            \"No minimum deposit\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Time deposits offer the highest interest rates in exchange for keeping money locked for a set period.\"\r\n    },\r\n    {\r\n        \"question\": \"What\'s the main disadvantage of time deposits?\",\r\n        \"answers\": [\r\n            \"Low interest\",\r\n            \"High fees\",\r\n            \"Limited access\",\r\n            \"No insurance\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"The main disadvantage of time deposits is limited access to funds during the term.\"\r\n    },\r\n    {\r\n        \"question\": \"Which account is best for emergency funds?\",\r\n        \"answers\": [\r\n            \"Time deposit\",\r\n            \"Regular savings\",\r\n            \"Investment account\",\r\n            \"Checking account\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Regular savings accounts provide the necessary balance of accessibility and interest for emergency funds.\"\r\n    }\r\n]'),
(9, 'Setting SMART Financial Goals', 'goals', 'Learn how to set and achieve Specific, Measurable, Achievable, Relevant, and Time-bound financial goals.', '<h4>Setting SMART Financial Goals</h4>\r\n    <p>Make your financial goals more achievable using the SMART framework:</p>\r\n    <h5>SMART Components</h5>\r\n    <ul>\r\n        <li><strong>Specific:</strong> Clear and precise objectives</li>\r\n        <li><strong>Measurable:</strong> Quantifiable targets</li>\r\n        <li><strong>Achievable:</strong> Realistic within your means</li>\r\n        <li><strong>Relevant:</strong> Aligned with your values</li>\r\n        <li><strong>Time-bound:</strong> Clear deadline</li>\r\n    </ul>\r\n    <h5>Example SMART Goals</h5>\r\n    <ol>\r\n        <li>Save ₱50,000 for emergency fund in 12 months</li>\r\n        <li>Pay off ₱100,000 credit card debt in 24 months</li>\r\n        <li>Save ₱250,000 for house down payment in 36 months</li>\r\n    </ol>', '2024-10-25', '2024-10-24 18:16:49', 'published', '[\r\n    {\r\n        \"question\": \"What does the \'S\' in SMART stand for?\",\r\n        \"answers\": [\r\n            \"Simple\",\r\n            \"Specific\",\r\n            \"Special\",\r\n            \"Systematic\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"\'S\' stands for Specific, meaning goals should be clear and precise.\"\r\n    },\r\n    {\r\n        \"question\": \"Why should goals be measurable?\",\r\n        \"answers\": [\r\n            \"To make them harder\",\r\n            \"To track progress\",\r\n            \"To impress others\",\r\n            \"To avoid change\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Measurable goals allow you to track progress and know when you\'ve achieved them.\"\r\n    },\r\n    {\r\n        \"question\": \"What makes a goal \'achievable\'?\",\r\n        \"answers\": [\r\n            \"It\'s impossible\",\r\n            \"It\'s very easy\",\r\n            \"It\'s realistic\",\r\n            \"It\'s vague\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Achievable goals are realistic within your means and circumstances.\"\r\n    },\r\n    {\r\n        \"question\": \"What does \'time-bound\' mean?\",\r\n        \"answers\": [\r\n            \"No deadline\",\r\n            \"Flexible timeline\",\r\n            \"Clear deadline\",\r\n            \"Indefinite period\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Time-bound goals have clear deadlines to create urgency and motivation.\"\r\n    },\r\n    {\r\n        \"question\": \"Which is a SMART goal example?\",\r\n        \"answers\": [\r\n            \"Save more money\",\r\n            \"Save money someday\",\r\n            \"Save ₱50,000 in 12 months\",\r\n            \"Maybe save something\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"\'Save ₱50,000 in 12 months\' is specific, measurable, achievable, relevant, and time-bound.\"\r\n    }\r\n]'),
(10, 'Planning for Major Life Goals', 'goals', 'Discover how to plan and save for major life milestones like buying a house, starting a family, or retirement.', '<h4>Planning for Major Life Goals</h4>\r\n    <p>Major life goals require careful financial planning. Here\'s how to prepare:</p>\r\n    <h5>Home Ownership</h5>\r\n    <ul>\r\n        <li>Calculate down payment needed</li>\r\n        <li>Research mortgage options</li>\r\n        <li>Plan for additional costs</li>\r\n        <li>Set timeline for purchase</li>\r\n    </ul>\r\n    <h5>Starting a Family</h5>\r\n    <ul>\r\n        <li>Medical expenses planning</li>\r\n        <li>Childcare costs</li>\r\n        <li>Education savings</li>\r\n        <li>Insurance needs</li>\r\n    </ul>\r\n    <h5>Retirement Planning</h5>\r\n    <ul>\r\n        <li>Calculate retirement needs</li>\r\n        <li>Review investment options</li>\r\n        <li>Consider healthcare costs</li>\r\n        <li>Plan for lifestyle changes</li>\r\n    </ul>', '2024-10-25', '2024-10-24 18:16:49', 'published', '[\r\n    {\r\n        \"question\": \"What should you consider first when planning home ownership?\",\r\n        \"answers\": [\r\n            \"Interior design\",\r\n            \"Down payment\",\r\n            \"Furniture\",\r\n            \"Paint colors\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Calculating the required down payment is the first step in planning for home ownership.\"\r\n    },\r\n    {\r\n        \"question\": \"What\'s most important when planning for a family?\",\r\n        \"answers\": [\r\n            \"Buying toys\",\r\n            \"Financial preparation\",\r\n            \"Choosing names\",\r\n            \"Party planning\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Financial preparation, including medical expenses and childcare costs, is crucial for family planning.\"\r\n    },\r\n    {\r\n        \"question\": \"When should retirement planning begin?\",\r\n        \"answers\": [\r\n            \"At retirement\",\r\n            \"As early as possible\",\r\n            \"When you\'re 60\",\r\n            \"After buying a house\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Retirement planning should begin as early as possible to maximize savings and compound interest.\"\r\n    },\r\n    {\r\n        \"question\": \"Which is NOT a retirement planning consideration?\",\r\n        \"answers\": [\r\n            \"Healthcare costs\",\r\n            \"Current fashion trends\",\r\n            \"Investment options\",\r\n            \"Lifestyle changes\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Current fashion trends are not relevant to retirement planning, unlike healthcare and lifestyle considerations.\"\r\n    },\r\n    {\r\n        \"question\": \"What should be included in mortgage planning?\",\r\n        \"answers\": [\r\n            \"Just the down payment\",\r\n            \"Only monthly payments\",\r\n            \"All associated costs\",\r\n            \"Just the interest\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"All associated costs, including taxes, insurance, and maintenance, should be considered in mortgage planning.\"\r\n    }\r\n]'),
(11, 'Short-Term vs Long-Term Goals', 'goals', 'Learn how to balance and prioritize your short-term and long-term financial goals effectively.', '<h4>Short-Term vs Long-Term Goals</h4>\r\n    <p>Understanding the difference between short-term and long-term goals helps in better financial planning:</p>\r\n    <h5>Short-Term Goals (0-2 years)</h5>\r\n    <ul>\r\n        <li>Emergency fund</li>\r\n        <li>Vacation savings</li>\r\n        <li>New appliances</li>\r\n        <li>Debt reduction</li>\r\n    </ul>\r\n    <h5>Medium-Term Goals (2-5 years)</h5>\r\n    <ul>\r\n        <li>Car purchase</li>\r\n        <li>Wedding expenses</li>\r\n        <li>Home down payment</li>\r\n        <li>Business startup</li>\r\n    </ul>\r\n    <h5>Long-Term Goals (5+ years)</h5>\r\n    <ul>\r\n        <li>Retirement savings</li>\r\n        <li>Children\'s education</li>\r\n        <li>Investment portfolio</li>\r\n        <li>Estate planning</li>\r\n    </ul>', '2024-10-25', '2024-10-24 18:16:49', 'published', '[\r\n    {\r\n        \"question\": \"What is considered a short-term goal?\",\r\n        \"answers\": [\r\n            \"Retirement savings\",\r\n            \"Building emergency fund\",\r\n            \"Children\'s college\",\r\n            \"Estate planning\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Building an emergency fund is typically a short-term goal (0-2 years).\"\r\n    },\r\n    {\r\n        \"question\": \"Which timeframe defines medium-term goals?\",\r\n        \"answers\": [\r\n            \"0-6 months\",\r\n            \"2-5 years\",\r\n            \"10+ years\",\r\n            \"20+ years\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Medium-term goals typically fall within the 2-5 year timeframe.\"\r\n    },\r\n    {\r\n        \"question\": \"What\'s an example of a long-term goal?\",\r\n        \"answers\": [\r\n            \"Vacation savings\",\r\n            \"New appliances\",\r\n            \"Retirement planning\",\r\n            \"Emergency fund\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Retirement planning is a long-term goal typically spanning many years.\"\r\n    },\r\n    {\r\n        \"question\": \"Which goal requires most immediate attention?\",\r\n        \"answers\": [\r\n            \"Short-term goals\",\r\n            \"Medium-term goals\",\r\n            \"Long-term goals\",\r\n            \"All equally\"\r\n        ],\r\n        \"correctAnswer\": 0,\r\n        \"explanation\": \"Short-term goals typically require most immediate attention due to their closer deadlines.\"\r\n    },\r\n    {\r\n        \"question\": \"What makes a goal \'medium-term\'?\",\r\n        \"answers\": [\r\n            \"It\'s very expensive\",\r\n            \"It takes 2-5 years\",\r\n            \"It\'s not important\",\r\n            \"It\'s very easy\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Medium-term goals are those that typically take 2-5 years to achieve.\"\r\n    }\r\n]'),
(12, 'Tracking Goal Progress', 'goals', 'Learn effective methods to track and measure your progress toward financial goals.', '<h4>Tracking Goal Progress</h4>\r\n    <p>Regular monitoring helps ensure you reach your financial goals:</p>\r\n    <h5>Setting Up Tracking Systems</h5>\r\n    <ul>\r\n        <li>Use budgeting apps</li>\r\n        <li>Create spreadsheets</li>\r\n        <li>Set up regular reviews</li>\r\n        <li>Monitor key metrics</li>\r\n    </ul>\r\n    <h5>Key Progress Indicators</h5>\r\n    <ul>\r\n        <li>Savings rate</li>\r\n        <li>Debt reduction</li>\r\n        <li>Investment returns</li>\r\n        <li>Net worth growth</li>\r\n    </ul>\r\n    <h5>Adjusting Goals</h5>\r\n    <ol>\r\n        <li>Review progress monthly</li>\r\n        <li>Identify obstacles</li>\r\n        <li>Make necessary adjustments</li>\r\n        <li>Celebrate milestones</li>\r\n    </ol>', '2024-10-25', '2024-10-24 18:16:49', 'published', '[\r\n    {\r\n        \"question\": \"Why is progress tracking important?\",\r\n        \"answers\": [\r\n            \"To make goals harder\",\r\n            \"To ensure goal achievement\",\r\n            \"To waste time\",\r\n            \"To spend more\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Tracking progress helps ensure goal achievement by keeping you accountable and showing areas for improvement.\"\r\n    },\r\n    {\r\n        \"question\": \"What is the best way to track financial goals?\",\r\n        \"answers\": [\r\n            \"Mentally remembering\",\r\n            \"Using systematic tracking tools\",\r\n            \"Asking friends\",\r\n            \"Ignoring them\"\r\n        ],\r\n        \"correctAnswer\": 1,\r\n        \"explanation\": \"Using systematic tracking tools like apps or spreadsheets provides accurate and consistent monitoring.\"\r\n    },\r\n    {\r\n        \"question\": \"How often should progress be reviewed?\",\r\n        \"answers\": [\r\n            \"Never\",\r\n            \"Once a year\",\r\n            \"Monthly\",\r\n            \"Only when failing\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Monthly reviews help identify issues early and allow for timely adjustments to stay on track.\"\r\n    },\r\n    {\r\n        \"question\": \"What is a key progress indicator for savings goals?\",\r\n        \"answers\": [\r\n            \"Social media likes\",\r\n            \"Number of bank accounts\",\r\n            \"Savings rate\",\r\n            \"Account age\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Savings rate is a key indicator that shows how much of your income you\'re saving relative to your goal.\"\r\n    },\r\n    {\r\n        \"question\": \"Why is celebrating milestones important?\",\r\n        \"answers\": [\r\n            \"To spend money\",\r\n            \"To show off\",\r\n            \"To maintain motivation\",\r\n            \"To delay progress\"\r\n        ],\r\n        \"correctAnswer\": 2,\r\n        \"explanation\": \"Celebrating milestones helps maintain motivation and provides positive reinforcement for good financial habits.\"\r\n    }\r\n]');

-- --------------------------------------------------------

--
-- Table structure for table `balances`
--

CREATE TABLE `balances` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(155) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp(),
  `month` varchar(7) NOT NULL,
  `color` varchar(7) DEFAULT '#000000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_alerts`
--

CREATE TABLE `budget_alerts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `budget_id` int(11) NOT NULL,
  `alert_type` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cumulative_balance`
--

CREATE TABLE `cumulative_balance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `comment` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goals`
--

CREATE TABLE `goals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `target_amount` decimal(10,2) NOT NULL,
  `current_amount` decimal(10,2) DEFAULT 0.00,
  `target_date` date NOT NULL,
  `category` varchar(50) NOT NULL,
  `priority` int(11) DEFAULT 0,
  `is_completed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  `status` varchar(20) DEFAULT 'active',
  `due_action` varchar(20) DEFAULT NULL,
  `action_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `goals`
--
DELIMITER $$
CREATE TRIGGER `update_goal_completion` BEFORE UPDATE ON `goals` FOR EACH ROW BEGIN
    IF NEW.current_amount >= NEW.target_amount THEN
        SET NEW.is_completed = TRUE;
    ELSE
        SET NEW.is_completed = FALSE;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `goal_alerts`
--

CREATE TABLE `goal_alerts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `goal_id` int(11) NOT NULL,
  `alert_type` varchar(20) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `goal_progress`
-- (See below for the actual view)
--
CREATE TABLE `goal_progress` (
`id` int(11)
,`user_id` int(11)
,`name` varchar(255)
,`target_amount` decimal(10,2)
,`current_amount` decimal(10,2)
,`progress_percentage` decimal(19,6)
,`target_date` date
,`category` varchar(50)
,`is_completed` tinyint(1)
);

-- --------------------------------------------------------

--
-- Table structure for table `incomes`
--

CREATE TABLE `incomes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotes`
--

CREATE TABLE `quotes` (
  `id` int(11) NOT NULL,
  `content` text NOT NULL,
  `author` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT 'finance',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotes`
--

INSERT INTO `quotes` (`id`, `content`, `author`, `category`, `created_at`) VALUES
(1, 'Do not save what is left after spending, but spend what is left after saving.', 'Warren Buffett', 'finance', '2024-11-20 10:55:02'),
(2, 'The habit of saving is itself an education; it fosters every virtue, teaches self-denial, cultivates the sense of order, trains to forethought.', 'T.T. Munger', 'savings', '2024-11-20 10:55:02'),
(3, 'Financial peace isn\'t the acquisition of stuff. It\'s learning to live on less than you make, so you can give money back and have money to invest.', 'Dave Ramsey', 'finance', '2024-11-20 10:55:02'),
(4, 'Never depend on a single income. Make an investment to create a second source.', 'Warren Buffett', 'investment', '2024-11-20 10:55:02'),
(5, 'A budget is telling your money where to go instead of wondering where it went.', 'Dave Ramsey', 'budgeting', '2024-11-20 10:55:02'),
(6, 'The goal isn\'t more money. The goal is living life on your terms.', 'Chris Brogan', 'motivation', '2024-11-20 10:55:02'),
(7, 'It\'s not how much money you make, but how much money you keep, how hard it works for you, and how many generations you keep it for.', 'Robert Kiyosaki', 'wealth', '2024-11-20 10:55:02'),
(8, 'Money looks better in the bank than on your feet.', 'Sophia Amoruso', 'savings', '2024-11-20 10:55:02');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_payments`
--

CREATE TABLE `subscription_payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_months` int(11) NOT NULL,
  `description` text NOT NULL,
  `features` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `name`, `price`, `duration_months`, `description`, `features`, `created_at`) VALUES
(1, 'Monthly Premium', 199.00, 1, 'Monthly subscription plan with all premium features', 'Custom budgets,Unlimited goals,PDF exports,Access to Financial Graphs,No advertisements', '2024-11-25 08:10:40'),
(2, 'Yearly Premium', 1999.00, 12, 'Yearly subscription plan with all premium features at a discounted rate', 'Custom budgets,Unlimited goals,PDF exports,Access to Financial Graphs,No advertisements', '2024-11-25 08:10:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `f_name` varchar(100) NOT NULL,
  `l_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `verify_token` varchar(255) NOT NULL,
  `verify_status` tinyint(2) NOT NULL DEFAULT 0 COMMENT '0=no,1=yes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_subscriptions`
--

CREATE TABLE `user_subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('active','cancelled','expired') NOT NULL DEFAULT 'active',
  `payment_status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `thumbnail_url` varchar(255) NOT NULL,
  `video_url` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `duration` varchar(10) NOT NULL,
  `level` varchar(20) NOT NULL,
  `views` int(11) DEFAULT 0,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `title`, `description`, `thumbnail_url`, `video_url`, `category`, `duration`, `level`, `views`, `date_added`, `status`) VALUES
(1, 'Emergency Funds 101', 'Everything you need to know about building an emergency fund.', '/assets/imgs/thumbnails/1.jpg', 'https://www.youtube.com/embed/tVGJqaOkqac', 'savings', '10:15', 'beginner', 0, '2024-11-21 05:57:40', 'active'),
(2, 'Why the secret to success is setting the right goals', 'Learn how to set and achieve meaningful goals.', '/assets/imgs/thumbnails/2.jpg', 'https://www.youtube.com/embed/L4N1q4RNi9I', 'goals', '11:51', 'intermediate', 0, '2024-11-21 05:57:40', 'active'),
(3, 'The Chinese Secret to Saving Money Revealed', 'Discover advanced Chinese techniques to maximize your savings potential.', '/assets/imgs/thumbnails/3.jpg', 'https://www.youtube.com/embed/ms1nTeFO7ps', 'savings', '10:56', 'advanced', 0, '2024-11-21 05:57:40', 'active');

-- --------------------------------------------------------

--
-- Structure for view `goal_progress`
--
DROP TABLE IF EXISTS `goal_progress`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `goal_progress`  AS SELECT `goals`.`id` AS `id`, `goals`.`user_id` AS `user_id`, `goals`.`name` AS `name`, `goals`.`target_amount` AS `target_amount`, `goals`.`current_amount` AS `current_amount`, `goals`.`current_amount`/ `goals`.`target_amount` * 100 AS `progress_percentage`, `goals`.`target_date` AS `target_date`, `goals`.`category` AS `category`, `goals`.`is_completed` AS `is_completed` FROM `goals` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `balances`
--
ALTER TABLE `balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_year_month` (`user_id`,`year`,`month`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_budget` (`user_id`,`name`,`month`);

--
-- Indexes for table `budget_alerts`
--
ALTER TABLE `budget_alerts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_alert` (`user_id`,`budget_id`,`alert_type`);

--
-- Indexes for table `cumulative_balance`
--
ALTER TABLE `cumulative_balance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `goals`
--
ALTER TABLE `goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `goal_alerts`
--
ALTER TABLE `goal_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `goal_id` (`goal_id`);

--
-- Indexes for table `incomes`
--
ALTER TABLE `incomes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `quotes`
--
ALTER TABLE `quotes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `subscription_id` (`subscription_id`);

--
-- Indexes for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `balances`
--
ALTER TABLE `balances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_alerts`
--
ALTER TABLE `budget_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cumulative_balance`
--
ALTER TABLE `cumulative_balance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goals`
--
ALTER TABLE `goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goal_alerts`
--
ALTER TABLE `goal_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incomes`
--
ALTER TABLE `incomes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotes`
--
ALTER TABLE `quotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `balances`
--
ALTER TABLE `balances`
  ADD CONSTRAINT `balances_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `cumulative_balance`
--
ALTER TABLE `cumulative_balance`
  ADD CONSTRAINT `cumulative_balance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `budgets` (`id`),
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `goals`
--
ALTER TABLE `goals`
  ADD CONSTRAINT `goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `goal_alerts`
--
ALTER TABLE `goal_alerts`
  ADD CONSTRAINT `goal_alerts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `goal_alerts_ibfk_2` FOREIGN KEY (`goal_id`) REFERENCES `goals` (`id`);

--
-- Constraints for table `incomes`
--
ALTER TABLE `incomes`
  ADD CONSTRAINT `incomes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  ADD CONSTRAINT `subscription_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `subscription_payments_ibfk_2` FOREIGN KEY (`subscription_id`) REFERENCES `user_subscriptions` (`id`);

--
-- Constraints for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD CONSTRAINT `user_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `user_subscriptions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
