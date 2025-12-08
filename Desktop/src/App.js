import './App.css';
import HelloWorld from './HelloWorld';
import Counter from './Counter';
import FilteredList from './FilteredList';

const produce = [
  {name: "Apple", type: "Fruit"},
  {name: "Pineapple", type: "Fruit"},
  {name: "Banana", type: "Fruit"},
  {name: "Pear", type: "Fruit"},
  {name: "Strawberry", type: "Fruit"},
  {name: "Orange", type: "Fruit"},
  {name: "Lettuce", type: "Vegetable"},
  {name: "Cucumber", type: "Vegetable"},
  {name: "Carrot", type: "Vegetable"},
  {name: "Onion", type: "Vegetable"}
];

function App() {
  return (
    <div className="App">
      <HelloWorld name="Julian" />
      <Counter />
      <FilteredList items={produce} />
    </div>
  );
}

export default App;
